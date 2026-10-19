<?php

namespace App\Http\Controllers\Admin;

use App\Models\Plan;
use App\Support\Ajax;
use App\Support\PlanModules;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlanController extends AdminController
{
    public function index(Request $request): View
    {
        $this->ensureCan('plans.view');

        $query = Plan::query();

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $plans = $query
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return view('admin.plans.index', [
            'plans' => $plans,
            'isPlatform' => true,
        ]);
    }

    public function create(): View
    {
        $this->ensureCan('plans.add');

        return view('admin.plans.form', [
            'plan' => null,
            'sellableModules' => PlanModules::sellableCatalog(),
            'coreModules' => PlanModules::CORE,
            'coreLabels' => self::coreLabelsPreview(),
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('plans.add');
        $plan = Plan::query()->create($this->validated($request));

        if ($request->expectsJson()) {
            return Ajax::success('Plan created.', $plan, 201);
        }

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan created.');
    }

    public function edit(Plan $plan): View
    {
        $this->ensureCan('plans.edit');

        return view('admin.plans.form', [
            'plan' => $plan,
            'sellableModules' => PlanModules::sellableCatalog(),
            'coreModules' => PlanModules::CORE,
            'coreLabels' => self::coreLabelsPreview(),
        ]);
    }

    public function update(Request $request, Plan $plan): JsonResponse|RedirectResponse
    {
        $this->ensureCan('plans.edit');
        $plan->update($this->validated($request, $plan));

        if ($request->expectsJson()) {
            return Ajax::success('Plan updated.', $plan->fresh());
        }

        return redirect()
            ->route('admin.plans.index')
            ->with('success', 'Plan updated.');
    }

    public function destroy(Request $request, Plan $plan): JsonResponse|RedirectResponse
    {
        $this->ensureCan('plans.delete');

        if ($plan->subscriptions()->exists()) {
            return $this->respondDestroyFailed(
                $request,
                'Cannot delete a plan that has subscriptions. Set status inactive instead.',
                422
            );
        }

        $plan->delete();

        return $this->respondDestroyed($request, 'Plan deleted.', 'admin.plans.index');
    }

    /**
     * @return array<string, mixed>
     */
    protected function validated(Request $request, ?Plan $plan = null): array
    {
        $sellable = array_keys(PlanModules::sellableCatalog());

        $data = $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('plans', 'code')->ignore($plan?->id)->whereNull('deleted_at'),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'price_monthly' => ['required', 'numeric', 'min:0'],
            'price_yearly' => ['required', 'numeric', 'min:0', 'gte:price_monthly'],
            'currency' => ['nullable', 'string', 'size:3'],
            'trial_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'max_branches' => ['nullable', 'integer', 'min:1'],
            'max_students' => ['nullable', 'integer', 'min:1'],
            'max_staff' => ['nullable', 'integer', 'min:1'],
            'modules_mode' => ['required', Rule::in(['all', 'selected'])],
            'modules' => ['nullable', 'array'],
            'modules.*' => ['string', Rule::in($sellable)],
            'benefits' => ['nullable', 'array'],
            'benefits.*' => ['nullable', 'string', 'max:200'],
            'is_public' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
        ], [
            'price_yearly.gte' => 'Yearly price must be greater than or equal to monthly price.',
        ]);

        $modules = ($data['modules_mode'] ?? 'selected') === 'all'
            ? ['*']
            : array_values(array_unique($data['modules'] ?? []));

        $benefits = array_values(array_filter(array_map(
            static fn ($b) => trim((string) $b),
            $data['benefits'] ?? []
        ), static fn ($b) => $b !== ''));

        return [
            'code' => strtolower($data['code']),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'price_monthly' => $data['price_monthly'],
            'price_yearly' => $data['price_yearly'],
            'currency' => strtoupper($data['currency'] ?? 'INR'),
            'trial_days' => (int) ($data['trial_days'] ?? 14),
            'max_branches' => $data['max_branches'] ?? null,
            'max_students' => $data['max_students'] ?? null,
            'max_staff' => $data['max_staff'] ?? null,
            'modules_json' => $modules,
            'benefits_json' => $benefits ?: null,
            'is_public' => $request->boolean('is_public'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'status' => $data['status'],
        ];
    }

    protected static function coreLabelsPreview(): string
    {
        $catalog = PlanModules::crmCatalog();

        return collect(PlanModules::CORE)
            ->map(fn (string $key) => $catalog[$key] ?? $key)
            ->take(6)
            ->implode(', ');
    }
}
