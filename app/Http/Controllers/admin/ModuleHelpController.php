<?php

namespace App\Http\Controllers\Admin;

use App\Models\ModuleHelp;
use App\Models\ModuleHelpStep;
use App\Services\InstituteOnboardingService;
use App\Services\ModuleHelpService;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ModuleHelpController extends AdminController
{
    public function __construct(
        protected ModuleHelpService $service
    ) {}

    public function index(): View
    {
        $guides = $this->service->listAll();
        $guidesPreview = $guides->map(fn ($g) => $g->toPreviewArray())->keyBy('id');

        return view('admin.module_help.index', compact('guides', 'guidesPreview'));
    }

    public function create(): View
    {
        $form = $this->service->formState(null, [
            'role_names' => old('role_names'),
            'steps' => old('steps'),
        ]);

        return view('admin.module_help.form', [
            'guide' => null,
            'moduleOptions' => $this->service->moduleOptions(),
            'roleOptions' => $this->service->roleOptions(),
            ...$form,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $this->validateGuide($request);
        $this->assertOnboardingSteps($request);
        $validated['is_onboarding'] = $request->boolean('is_onboarding');
        $steps = $this->extractSteps($request);
        $guide = $this->service->create($validated, $steps);

        if ($request->expectsJson()) {
            return Ajax::success('Guide created.', $guide, 201);
        }

        return redirect()
            ->route('admin.module_help.index')
            ->with('success', 'Guide created.');
    }

    public function edit(ModuleHelp $moduleHelp): View
    {
        $moduleHelp->load(['steps', 'roleRows']);
        $form = $this->service->formState($moduleHelp, [
            'role_names' => old('role_names'),
            'steps' => old('steps'),
        ]);

        return view('admin.module_help.form', [
            'guide' => $moduleHelp,
            'moduleOptions' => $this->service->moduleOptions(),
            'roleOptions' => $this->service->roleOptions(),
            ...$form,
        ]);
    }

    public function update(Request $request, ModuleHelp $moduleHelp): JsonResponse|RedirectResponse
    {
        $validated = $this->validateGuide($request, $moduleHelp);
        $this->assertOnboardingSteps($request);
        $validated['is_onboarding'] = $request->boolean('is_onboarding');
        $steps = $this->extractSteps($request);
        $guide = $this->service->update($moduleHelp, $validated, $steps);

        if ($request->expectsJson()) {
            return Ajax::success('Guide updated.', $guide);
        }

        return redirect()
            ->route('admin.module_help.index')
            ->with('success', 'Guide updated.');
    }

    public function destroy(Request $request, ModuleHelp $moduleHelp): JsonResponse|RedirectResponse
    {
        $this->service->delete($moduleHelp);

        return $this->respondDestroyed($request, 'Guide deleted.', 'admin.module_help.index');
    }

    /**
     * Institute-side: full guide + steps JSON (role-scoped).
     */
    public function show(string $moduleKey): JsonResponse
    {
        $user = auth()->user();
        if (! $user) {
            return Ajax::error('Unauthenticated.', null, 401);
        }

        $guide = $this->service->findActiveForUser($moduleKey, $user);
        if (! $guide) {
            return Ajax::error('Guide not found.', null, 404);
        }

        return Ajax::success('OK', $guide->toPreviewArray());
    }

    /**
     * @return array<string, mixed>
     */
    protected function validateGuide(Request $request, ?ModuleHelp $guide = null): array
    {
        $moduleKeys = array_keys($this->service->moduleOptions());
        $roleKeys = array_keys($this->service->roleOptions());

        return $request->validate([
            'module_key' => [
                'required',
                'string',
                Rule::in($moduleKeys),
                Rule::unique('module_helps', 'module_key')
                    ->ignore($guide?->id)
                    ->whereNull('deleted_at'),
            ],
            'title' => ['required', 'string', 'max:180'],
            'intro' => ['nullable', 'string', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:999'],
            'status' => ['required', Rule::in([ModuleHelp::STATUS_ACTIVE, ModuleHelp::STATUS_INACTIVE])],
            'is_onboarding' => ['nullable', 'boolean'],
            'role_names' => ['required', 'array', 'min:1'],
            'role_names.*' => ['string', Rule::in($roleKeys)],
            'steps' => ['required', 'array', 'min:1'],
            'steps.*.id' => ['nullable', 'integer'],
            'steps.*.title' => ['required', 'string', 'max:180'],
            'steps.*.description' => ['nullable', 'string', 'max:5000'],
            'steps.*.media_type' => ['required', Rule::in(array_keys(ModuleHelpStep::MEDIA_TYPES))],
            'steps.*.media_caption' => ['nullable', 'string', 'max:255'],
            'steps.*.embed_url' => ['nullable', 'url', 'max:500'],
            'steps.*.cta_label' => ['nullable', 'string', 'max:120', 'required_with:steps.*.cta_url'],
            'steps.*.target' => ['nullable', 'string', 'max:180', 'regex:/^[^<>]*$/'],
            'steps.*.placement' => ['nullable', Rule::in(['top', 'bottom', 'left', 'right'])],
            'steps.*.is_required' => ['nullable', 'boolean'],
            'steps.*.completion_key' => ['nullable', 'string', Rule::in(array_keys(InstituteOnboardingService::COMPLETION_KEYS))],
            'steps.*.cta_url' => [
                'nullable',
                'string',
                'max:500',
                'required_with:steps.*.cta_label',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $url = trim((string) $value);
                    if ($url === '') {
                        return;
                    }

                    $ok = str_starts_with($url, '/')
                        || (bool) filter_var($url, FILTER_VALIDATE_URL);

                    if (! $ok) {
                        $fail('CTA URL must be an absolute http(s) URL or an app path starting with /.');
                    }
                },
            ],
            'steps.*.image' => ['nullable', 'image', 'max:5120'],
            'steps.*.video' => ['nullable', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:51200'],
        ], [
            'module_key.unique' => 'A guide for this module already exists.',
            'role_names.required' => 'Select at least one role.',
            'steps.required' => 'Add at least one step.',
            'steps.*.cta_label.required_with' => 'CTA button text is required when a CTA URL is set.',
            'steps.*.cta_url.required_with' => 'CTA URL is required when CTA button text is set.',
        ]);
    }

    protected function assertOnboardingSteps(Request $request): void
    {
        if (! $request->boolean('is_onboarding')) {
            return;
        }

        $allowed = array_keys(InstituteOnboardingService::COMPLETION_KEYS);
        $errors = [];

        foreach (array_values($request->input('steps', [])) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $key = (string) ($row['completion_key'] ?? '');
            if (! in_array($key, $allowed, true)) {
                $errors['steps.'.$index.'.completion_key'] = 'Choose what saving this record completes.';
            }
        }

        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function extractSteps(Request $request): array
    {
        $raw = $request->input('steps', []);
        if (! is_array($raw)) {
            return [];
        }

        $out = [];
        foreach (array_values($raw) as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            $mediaType = (string) ($row['media_type'] ?? ModuleHelpStep::MEDIA_NONE);
            $file = null;
            if ($mediaType === ModuleHelpStep::MEDIA_IMAGE) {
                $uploaded = $request->file('steps.'.$index.'.image');
                $file = $uploaded instanceof UploadedFile ? $uploaded : null;
            } elseif ($mediaType === ModuleHelpStep::MEDIA_VIDEO) {
                $uploaded = $request->file('steps.'.$index.'.video');
                $file = $uploaded instanceof UploadedFile ? $uploaded : null;
            }

            $out[] = [
                'id' => $row['id'] ?? null,
                'title' => $row['title'] ?? '',
                'description' => $row['description'] ?? null,
                'media_type' => $mediaType,
                'media_caption' => $row['media_caption'] ?? null,
                'embed_url' => $row['embed_url'] ?? null,
                'cta_label' => $row['cta_label'] ?? null,
                'cta_url' => $row['cta_url'] ?? null,
                'target' => $row['target'] ?? null,
                'placement' => $row['placement'] ?? 'bottom',
                'is_required' => $row['is_required'] ?? false,
                'completion_key' => $row['completion_key'] ?? null,
                'file' => $file,
            ];
        }

        return $out;
    }
}
