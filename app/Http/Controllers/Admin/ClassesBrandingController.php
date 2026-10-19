<?php

namespace App\Http\Controllers\Admin;

use App\Models\Institute;
use App\Support\Ajax;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ClassesBrandingController extends AdminController
{
    public function edit(): View|RedirectResponse
    {
        $this->ensureCan('classes.view');

        $institute = $this->resolveInstitute();
        if (! $institute) {
            return redirect()
                ->route('admin.institutes.index')
                ->with('error', 'Select an institute from Institutes to manage branding, or log in as institute admin.');
        }

        return view('admin.classes.branding', compact('institute'));
    }

    public function update(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('classes.edit');

        $institute = $this->resolveInstitute();
        abort_unless($institute, 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'email' => ['required', 'email', 'max:150'],
            'phone' => ['nullable', 'string', 'regex:/^[0-9]{10,15}$/'],
            'gstin' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:1000'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'pincode' => ['nullable', 'string', 'max:20'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'logo' => [
                Rule::requiredIf(fn () => blank($institute->logo_path)),
                'nullable',
                'image',
                'max:2048',
            ],
            'signature' => [
                Rule::requiredIf(fn () => blank($institute->signature_path)),
                'nullable',
                'image',
                'max:2048',
            ],
            'stamp' => [
                Rule::requiredIf(fn () => blank($institute->stamp_path)),
                'nullable',
                'image',
                'max:2048',
            ],
            'fees_receipt_terms' => ['nullable', 'array'],
            'fees_receipt_terms.*' => ['nullable', 'string', 'max:500'],
        ]);

        $settings = $institute->settings_json ?? [];
        if (array_key_exists('tagline', $validated)) {
            $settings['tagline'] = $validated['tagline'];
        }

        $terms = collect($validated['fees_receipt_terms'] ?? [])
            ->map(fn ($term) => trim((string) $term))
            ->filter()
            ->values()
            ->all();
        $settings['fees_receipt_terms'] = $terms;

        $payload = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'gstin' => $validated['gstin'] ?? null,
            'address' => $validated['address'] ?? null,
            'city' => $validated['city'] ?? null,
            'state' => $validated['state'] ?? null,
            'pincode' => $validated['pincode'] ?? null,
            'settings_json' => $settings,
        ];

        foreach (['logo' => 'logo_path', 'signature' => 'signature_path', 'stamp' => 'stamp_path'] as $fileKey => $column) {
            if (! $request->hasFile($fileKey)) {
                continue;
            }

            $dir = match ($fileKey) {
                'logo' => 'institutes/logos',
                'signature' => 'institutes/signatures',
                'stamp' => 'institutes/stamps',
            };

            $oldPath = $institute->{$column};
            $payload[$column] = $request->file($fileKey)->store($dir, 'public');

            if ($oldPath && $oldPath !== $payload[$column]) {
                Storage::disk('public')->delete($oldPath);
            }
        }

        $institute->update($payload);

        if ($request->expectsJson()) {
            return Ajax::success('Institute settings updated.', $institute->fresh());
        }

        return redirect()
            ->route('admin.classes')
            ->with('success', 'Institute settings updated.');
    }

    public function updateTheme(Request $request): JsonResponse|RedirectResponse
    {
        $this->ensureCan('classes.edit');

        $institute = $this->resolveInstitute();
        abort_unless($institute, 404);

        $validated = $request->validate([
            'theme_primary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_primary_dark' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_secondary' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'theme_secondary_dark' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);

        $settings = $institute->settings_json ?? [];
        $settings['theme'] = [
            'primary' => $validated['theme_primary'],
            'primary_dark' => $validated['theme_primary_dark'],
            'secondary' => $validated['theme_secondary'],
            'secondary_dark' => $validated['theme_secondary_dark'],
        ];

        $institute->update(['settings_json' => $settings]);

        if ($request->expectsJson()) {
            return Ajax::success('Theme settings updated.', $institute->fresh());
        }

        return redirect()
            ->route('admin.classes')
            ->with('success', 'Theme settings updated.');
    }

    protected function resolveInstitute(): ?Institute
    {
        $user = auth()->user();
        if (! $user) {
            return null;
        }

        if (! $user->isPlatformUser() && $user->institute_id) {
            return Institute::query()->find($user->institute_id);
        }

        return null;
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
