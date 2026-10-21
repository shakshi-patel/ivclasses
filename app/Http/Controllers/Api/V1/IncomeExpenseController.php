<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\IeCategory;
use App\Models\IncomeExpense;
use App\Services\IncomeExpenseService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class IncomeExpenseController extends ApiController
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense.view');

        $query = $this->entryQuery()->with(['category:id,name,type', 'branch:id,name', 'creator:id,name']);

        if ($request->filled('branch_id')) {
            $query->where('branch_id', (int) $request->input('branch_id'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->input('payment_method'));
        }
        if ($request->filled('start_date')) {
            $query->whereDate('entry_date', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('entry_date', '<=', $request->input('end_date'));
        }

        $incomeTotal = (float) (clone $query)->whereRaw('LOWER(type) = ?', ['income'])->sum('amount');
        $expenseTotal = (float) (clone $query)->whereRaw('LOWER(type) = ?', ['expense'])->sum('amount');
        $canShowIncome = $this->actor()->can('income_expense.show_total_income');

        if ($request->filled('type')) {
            $query->whereRaw('LOWER(type) = ?', [strtolower((string) $request->input('type'))]);
        }

        return $this->paginated(
            'OK',
            $query->latest('entry_date')->latest('id')->paginate($this->perPage())
                ->through(fn (IncomeExpense $row) => $this->mapEntry($row)),
            [
                'totals' => [
                    'income' => $canShowIncome ? $incomeTotal : null,
                    'expense' => $expenseTotal,
                    'balance' => $canShowIncome ? round($incomeTotal - $expenseTotal, 2) : null,
                ],
            ]
        );
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense.add');

        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);
        $branchIds = BranchScope::for($this->actor())->allowedBranchIdsForInstitute($instituteId);

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds ?: [0])],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('ie_categories', 'id')->where(fn ($q) => $q->where('institute_id', $instituteId)),
            ],
            'title' => ['nullable', 'string', 'max:150'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'entry_date' => ['required', 'date'],
            'payment_method' => ['required', 'string', 'max:30'],
            'note' => ['nullable', 'string', 'max:2000'],
        ]);

        $entry = $this->incomeExpenseService->create($validated);

        return Ajax::success('Entry saved.', $this->mapEntry($entry->load(['category:id,name,type', 'branch:id,name', 'creator:id,name'])), 201);
    }

    public function destroy(IncomeExpense $incomeExpense): JsonResponse
    {
        $this->ensureCan('income_expense.delete');
        $this->assertEntryVisible($incomeExpense);
        $this->incomeExpenseService->delete($incomeExpense);

        return Ajax::success('Entry deleted.');
    }

    public function categories(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense_category.view');

        $instituteId = (int) $this->actor()->institute_id;
        $this->incomeExpenseService->ensureDefaultCategories($instituteId);

        $query = IeCategory::query()->where('institute_id', $instituteId);
        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }
        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        return $this->paginated(
            'OK',
            $query->orderBy('sort_order')->orderBy('type')->orderBy('name')->paginate($this->perPage())
                ->through(fn (IeCategory $row) => $this->mapCategory($row))
        );
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense_category.add');
        $validated = $this->validateCategory($request);
        $validated['status'] = $validated['status'] ?? 'active';
        $validated['created_by'] = $this->actor()->id;
        $validated['updated_by'] = $this->actor()->id;
        $category = IeCategory::query()->create($validated);

        return Ajax::success('Category created.', $this->mapCategory($category), 201);
    }

    public function updateCategory(Request $request, IeCategory $ieCategory): JsonResponse
    {
        $this->ensureCan('income_expense_category.edit');
        $this->assertCategoryVisible($ieCategory);
        $validated = $this->validateCategory($request, $ieCategory);
        $validated['updated_by'] = $this->actor()->id;
        $ieCategory->update($validated);

        return Ajax::success('Category updated.', $this->mapCategory($ieCategory->fresh()));
    }

    public function statusCategory(IeCategory $ieCategory): JsonResponse
    {
        $this->ensureCan('income_expense_category.status');
        $this->assertCategoryVisible($ieCategory);
        $ieCategory->update([
            'status' => $ieCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $ieCategory->status === 'active' ? 'Category activated.' : 'Category deactivated.',
            $this->mapCategory($ieCategory)
        );
    }

    protected function entryQuery()
    {
        $query = IncomeExpense::query()->where('institute_id', $this->actor()->institute_id);
        $scope = BranchScope::for($this->actor());
        if ($scope->isRestricted()) {
            $ids = $scope->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('branch_id', $ids ?: [0])->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    protected function assertEntryVisible(IncomeExpense $entry): void
    {
        if ((int) $entry->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Entry not found.');
        }
        if ($entry->branch_id) {
            BranchScope::for($this->actor())->assertCanAccessBranch((int) $entry->branch_id);
        }
    }

    protected function assertCategoryVisible(IeCategory $category): void
    {
        if ((int) $category->institute_id !== (int) $this->actor()->institute_id) {
            abort(404, 'Category not found.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCategory(Request $request, ?IeCategory $category = null): array
    {
        $instituteId = (int) $this->actor()->institute_id;
        $request->merge(['institute_id' => $instituteId]);

        return $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'name' => [
                'required',
                'string',
                'max:100',
                Rule::unique('ie_categories', 'name')
                    ->where(fn ($q) => $q
                        ->where('institute_id', $instituteId)
                        ->where('type', $request->input('type'))
                        ->whereNull('deleted_at'))
                    ->ignore($category?->id),
            ],
            'type' => ['required', 'in:income,expense'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapEntry(IncomeExpense $row): array
    {
        return [
            'id' => $row->id,
            'type' => $row->type,
            'title' => $row->title,
            'amount' => (float) $row->amount,
            'entry_date' => $row->entry_date?->toDateString(),
            'payment_method' => $row->payment_method,
            'note' => $row->note,
            'fee_payment_id' => $row->fee_payment_id,
            'category' => $row->category ? [
                'id' => $row->category->id,
                'name' => $row->category->name,
                'type' => $row->category->type,
            ] : null,
            'branch' => $row->branch ? [
                'id' => $row->branch->id,
                'name' => $row->branch->name,
            ] : null,
            'creator' => $row->creator ? [
                'id' => $row->creator->id,
                'name' => $row->creator->name,
            ] : null,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function mapCategory(IeCategory $row): array
    {
        return [
            'id' => $row->id,
            'name' => $row->name,
            'type' => $row->type,
            'sort_order' => $row->sort_order,
            'status' => $row->status,
        ];
    }
}
