<?php

namespace App\Http\Controllers\Admin;

use App\Models\EmailTemplate;
use App\Services\MailTemplateService;
use App\Support\Ajax;
use App\Support\EmailTemplateRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EmailTemplateController extends AdminController
{
    public function __construct(
        protected MailTemplateService $mailTemplates
    ) {}

    public function index(): View
    {
        $this->mailTemplates->syncFromRegistry();

        $templates = EmailTemplate::query()
            ->orderBy('name')
            ->get()
            ->keyBy('key');

        $registryKeys = EmailTemplateRegistry::keys();
        $trashedSystemKeys = EmailTemplate::onlyTrashed()
            ->whereIn('key', $registryKeys)
            ->pluck('key')
            ->all();

        $rows = collect(EmailTemplateRegistry::all())
            ->reject(fn (array $meta, string $key) => in_array($key, $trashedSystemKeys, true))
            ->map(function (array $meta, string $key) use ($templates) {
                /** @var EmailTemplate|null $row */
                $row = $templates->get($key);

                return [
                    'key' => $key,
                    'name' => $row?->name ?? $meta['name'],
                    'description' => $meta['description'],
                    'subject' => $row?->subject ?? $meta['default_subject'],
                    'status' => $row?->status ?? EmailTemplate::STATUS_ACTIVE,
                    'updated_at' => $row?->updated_at,
                    'id' => $row?->id,
                    'is_system' => true,
                ];
            });

        $custom = $templates
            ->reject(fn (EmailTemplate $t) => in_array($t->key, $registryKeys, true))
            ->map(fn (EmailTemplate $row) => [
                'key' => $row->key,
                'name' => $row->name,
                'description' => 'Custom template',
                'subject' => $row->subject,
                'status' => $row->status,
                'updated_at' => $row->updated_at,
                'id' => $row->id,
                'is_system' => false,
            ])
            ->values();

        $rows = $rows->concat($custom)->values();

        return view('admin.email_templates.index', [
            'rows' => $rows,
        ]);
    }

    public function create(): View
    {
        return view('admin.email_templates.form', [
            'template' => new EmailTemplate([
                'status' => EmailTemplate::STATUS_ACTIVE,
                'body_html' => $this->blankBodyHtml(),
            ]),
            'meta' => [
                'description' => 'Custom template. Use {{variable}} placeholders; your send code must pass matching variables.',
                'variables' => [],
            ],
            'mode' => 'create',
            'isSystem' => false,
        ]);
    }

    public function store(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'key' => [
                'required',
                'string',
                'max:80',
                'regex:/^[a-z][a-z0-9_]*(\.[a-z0-9_]+)*$/',
                Rule::unique('email_templates', 'key')->whereNull('deleted_at'),
                Rule::notIn(EmailTemplateRegistry::keys()),
            ],
            'name' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'status' => ['required', Rule::in([EmailTemplate::STATUS_ACTIVE, EmailTemplate::STATUS_INACTIVE])],
        ], [
            'key.regex' => 'Key must be lowercase letters, numbers, dots/underscores (e.g. custom.welcome).',
            'key.not_in' => 'That key is reserved for a system template.',
        ]);

        $template = EmailTemplate::query()->create($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Email template created.', [
                'template' => $template,
                'redirect' => route('admin.email_templates.show', $template->key),
            ]);
        }

        return redirect()
            ->route('admin.email_templates.show', $template->key)
            ->with('success', 'Email template created.');
    }

    public function show(string $key): View|RedirectResponse
    {
        $template = $this->findTemplate($key);
        if (! $template) {
            return redirect()
                ->route('admin.email_templates.index')
                ->with('error', 'Email template not found.');
        }

        $meta = EmailTemplateRegistry::definition($key) ?? [
            'description' => 'Custom template',
            'variables' => [],
        ];

        return view('admin.email_templates.show', [
            'template' => $template->loadMissing(['creator', 'updater']),
            'meta' => $meta,
            'isSystem' => EmailTemplateRegistry::definition($key) !== null,
        ]);
    }

    public function edit(string $key): View|RedirectResponse
    {
        $template = $this->findTemplate($key);
        if (! $template) {
            return redirect()
                ->route('admin.email_templates.index')
                ->with('error', 'Email template not found.');
        }

        $meta = EmailTemplateRegistry::definition($key) ?? [
            'description' => 'Custom template. Use {{variable}} placeholders.',
            'variables' => [],
        ];

        return view('admin.email_templates.form', [
            'template' => $template,
            'meta' => $meta,
            'mode' => 'edit',
            'isSystem' => EmailTemplateRegistry::definition($key) !== null,
        ]);
    }

    public function update(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $template = $this->findTemplate($key);
        if (! $template) {
            abort(404);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'status' => ['required', Rule::in([EmailTemplate::STATUS_ACTIVE, EmailTemplate::STATUS_INACTIVE])],
        ]);

        $template->update($validated);

        if ($request->expectsJson()) {
            return Ajax::success('Email template updated.', $template->fresh());
        }

        return redirect()
            ->route('admin.email_templates.index')
            ->with('success', 'Email template updated.');
    }

    public function reset(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $meta = EmailTemplateRegistry::definition($key);
        if (! $meta) {
            if ($request->expectsJson()) {
                return Ajax::error('Only system templates can be reset.');
            }

            return redirect()
                ->route('admin.email_templates.index')
                ->with('error', 'Only system templates can be reset.');
        }

        EmailTemplate::query()->updateOrCreate(
            ['key' => $key],
            [
                'name' => $meta['name'],
                'subject' => $meta['default_subject'],
                'body_html' => $meta['default_body'],
                'status' => EmailTemplate::STATUS_ACTIVE,
            ]
        );

        if ($request->expectsJson()) {
            return Ajax::success('Template reset to default content.');
        }

        return redirect()
            ->route('admin.email_templates.edit', $key)
            ->with('success', 'Template reset to default content.');
    }

    public function destroy(Request $request, string $key): JsonResponse|RedirectResponse
    {
        $template = EmailTemplate::query()->where('key', $key)->first();
        if ($template) {
            $template->delete();
        }

        if ($request->expectsJson()) {
            return Ajax::success('Email template deleted.');
        }

        return redirect()
            ->route('admin.email_templates.index')
            ->with('success', 'Email template deleted.');
    }

    protected function findTemplate(string $key): ?EmailTemplate
    {
        if (EmailTemplateRegistry::definition($key)) {
            $this->mailTemplates->syncFromRegistry();
        }

        return EmailTemplate::query()->where('key', $key)->first();
    }

    protected function blankBodyHtml(): string
    {
        return <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
  <h2 style="margin-bottom: 8px; color: #0f5c4d;">Heading</h2>
  <p>Write your email body here. Use placeholders like {{name}}.</p>
  <p>IV Classes</p>
</div>
HTML;
    }
}
