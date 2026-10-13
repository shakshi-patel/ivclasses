<?php

namespace App\Http\Controllers\Admin;

use App\Models\IeCategory;
use App\Models\IncomeExpense;
use App\Models\Institute;
use App\Services\IncomeExpenseService;
use App\Support\Ajax;
use App\Support\BranchScope;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IncomeExpenseController extends AdminController
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('income_expense.view');

        $filters = $this->listFilterVars($request, ['branches']);
        $instituteId = $this->filterInstituteId($request);

        if ($instituteId) {
            $this->incomeExpenseService->ensureDefaultCategories($instituteId);
        }

        $query = $this->entryQuery()->with(['category:id,name,type', 'branch:id,name', 'creator:id,name,email']);

        $this->applyInstituteFilter($query, $request);

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

        // Summary cards use the same scope as the list (except type filter).
        $incomeTotal = (float) (clone $query)->whereRaw('LOWER(type) = ?', ['income'])->sum('amount');
        $expenseTotal = (float) (clone $query)->whereRaw('LOWER(type) = ?', ['expense'])->sum('amount');
        $totals = [
            'income' => $incomeTotal,
            'expense' => $expenseTotal,
            'balance' => $incomeTotal - $expenseTotal,
        ];

        if ($request->filled('type')) {
            $query->whereRaw('LOWER(type) = ?', [strtolower((string) $request->input('type'))]);
        }

        $entries = $query->latest('entry_date')->latest('id')->get();

        $categories = $instituteId
            ? IeCategory::query()->where('institute_id', $instituteId)->where('status', 'active')->orderBy('type')->orderBy('sort_order')->orderBy('name')->get()
            : collect();

        $canShowTotalIncome = auth()->user()?->isPlatformUser() || auth()->user()?->can('income_expense.show_total_income');

        return view('admin.income_expense.income_expense', array_merge($filters, compact('entries', 'totals', 'categories', 'canShowTotalIncome')));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense.add');

        $instituteId = $this->resolveInstituteId();
        abort_unless($instituteId, 422, 'Institute is required.');

        if (! auth()->user()?->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        $branchIds = BranchScope::for()->allowedBranchIdsForInstitute($instituteId);

        $validated = $request->validate([
            'institute_id' => ['required', 'integer', 'exists:institutes,id'],
            'branch_id' => ['nullable', 'integer', Rule::in($branchIds)],
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

        return Ajax::success('Entry saved.', $entry->load(['category', 'creator']), 201);
    }

    public function destroy(Request $request, IncomeExpense $incomeExpense): JsonResponse|RedirectResponse
    {
        $this->ensureCan('income_expense.delete');
        $this->assertVisible($incomeExpense);

        $this->incomeExpenseService->delete($incomeExpense);

        return $this->respondDestroyed($request, 'Entry deleted.', 'admin.income_expense');
    }

    protected function entryQuery()
    {
        $query = IncomeExpense::query();
        $user = auth()->user();

        if ($user && ! $user->isPlatformUser()) {
            $query->where('institute_id', $user->institute_id);
        }

        if (BranchScope::for()->isRestricted()) {
            $ids = BranchScope::for()->branchIds();
            $query->where(function ($q) use ($ids) {
                $q->whereIn('branch_id', $ids)->orWhereNull('branch_id');
            });
        }

        return $query;
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return Institute::query()->orderBy('id')->value('id');
    }

    protected function assertVisible(IncomeExpense $entry): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $entry->institute_id !== (int) $user->institute_id) {
            abort(404);
        }

        if ($entry->branch_id) {
            BranchScope::for($user)->assertCanAccessBranch((int) $entry->branch_id);
        }
    }

    protected function ensureCan(string $permission): void
    {
        $user = auth()->user();
        if ($user && ($user->isPlatformUser() || $user->can($permission))) {
            return;
        }

        abort(403, 'Unauthorized.');
    }
}
