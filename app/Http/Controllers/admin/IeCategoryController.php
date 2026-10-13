<?php

namespace App\Http\Controllers\Admin;

use App\Models\IeCategory;
use App\Models\Institute;
use App\Services\IncomeExpenseService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class IeCategoryController extends AdminController
{
    public function __construct(
        protected IncomeExpenseService $incomeExpenseService
    ) {}

    public function index(Request $request): View
    {
        $this->ensureCan('income_expense_category.view');

        $filters = $this->listFilterVars($request, []);
        $instituteId = $this->filterInstituteId($request);
        if ($instituteId) {
            $this->incomeExpenseService->ensureDefaultCategories($instituteId);
        }

        $query = IeCategory::query()->with('institute:id,name');

        if (! $this->isPlatform()) {
            $ownId = $this->resolveInstituteId();
            if ($ownId) {
                $query->where('institute_id', $ownId);
            }
        } else {
            $this->applyInstituteFilter($query, $request);
        }

        if ($request->filled('type') && in_array($request->input('type'), ['income', 'expense'], true)) {
            $query->where('type', $request->input('type'));
        }

        if ($request->filled('status') && in_array($request->input('status'), ['active', 'inactive'], true)) {
            $query->where('status', $request->input('status'));
        }

        $categories = $query
            ->orderBy('sort_order')
            ->orderBy('type')
            ->orderBy('name')
            ->get();

        return view('admin.master.income_expense.income_expense', array_merge($filters, [
            'categories' => $categories,
            'institutes' => $this->instituteOptions(),
        ]));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensureCan('income_expense_category.add');

        $validated = $this->validateCategory($request);
        $validated['created_by'] = auth()->id();
        $validated['updated_by'] = auth()->id();
        $category = IeCategory::query()->create($validated);
        $category->load('institute:id,name');

        return Ajax::success('Category created.', $category, 201);
    }

    public function update(Request $request, IeCategory $ieCategory): JsonResponse
    {
        $this->ensureCan('income_expense_category.edit');
        $this->assertVisible($ieCategory);

        $validated = $this->validateCategory($request, $ieCategory);
        $validated['updated_by'] = auth()->id();
        $ieCategory->update($validated);

        return Ajax::success('Category updated.', $ieCategory->fresh()->load('institute:id,name'));
    }

    public function destroy(Request $request, IeCategory $ieCategory): JsonResponse|RedirectResponse
    {
        $this->ensureCan('income_expense_category.delete');
        $this->assertVisible($ieCategory);

        if ($ieCategory->entries()->exists()) {
            return $this->respondDestroyFailed($request, 'Cannot delete — entries exist for this category.');
        }

        $ieCategory->delete();

        return $this->respondDestroyed($request, 'Category deleted.', 'admin.master.income_expense');
    }

    public function toggleStatus(IeCategory $ieCategory): JsonResponse
    {
        $this->ensureCan('income_expense_category.status');
        $this->assertVisible($ieCategory);

        $ieCategory->update([
            'status' => $ieCategory->status === 'active' ? 'inactive' : 'active',
        ]);

        return Ajax::success(
            $ieCategory->status === 'active' ? 'Category activated.' : 'Category deactivated.',
            ['id' => $ieCategory->id, 'status' => $ieCategory->status]
        );
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateCategory(Request $request, ?IeCategory $category = null): array
    {
        $user = auth()->user();
        $instituteId = $user && ! $user->isPlatformUser()
            ? (int) $user->institute_id
            : (int) $request->input('institute_id', $user?->institute_id);

        if ($user && ! $user->isPlatformUser()) {
            $request->merge(['institute_id' => $instituteId]);
        }

        abort_unless($instituteId, 422, 'Institute is required.');

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
            'status' => ['required', 'in:active,inactive'],
        ]);
    }

    protected function instituteOptions()
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return Institute::query()->where('id', $user->institute_id)->get(['id', 'name']);
        }

        return Institute::query()->orderBy('name')->get(['id', 'name']);
    }

    protected function resolveInstituteId(): ?int
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser()) {
            return (int) $user->institute_id;
        }

        return null;
    }

    protected function assertVisible(IeCategory $category): void
    {
        $user = auth()->user();
        if ($user && ! $user->isPlatformUser() && (int) $category->institute_id !== (int) $user->institute_id) {
            abort(404);
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
