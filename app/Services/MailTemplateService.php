<?php

namespace App\Services;

use App\Mail\TemplateMail;
use App\Models\EmailTemplate;
use App\Support\EmailTemplateRegistry;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\View;

class MailTemplateService
{
    public function __construct(
        protected AppSettingsService $settings
    ) {}

    /**
     * Ensure all registry keys exist in DB (upsert defaults without overwriting edited rows).
     */
    public function syncFromRegistry(bool $overwriteContent = false): void
    {
        foreach (EmailTemplateRegistry::all() as $key => $meta) {
            // Soft-deleted system templates stay deleted until manually restored/re-seeded.
            if (EmailTemplate::onlyTrashed()->where('key', $key)->exists()) {
                continue;
            }

            $existing = EmailTemplate::query()->where('key', $key)->first();
            if ($existing && ! $overwriteContent) {
                if ($existing->name !== $meta['name']) {
                    $existing->update(['name' => $meta['name']]);
                }
                continue;
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
        }
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array{subject: string, html: string, used_db: bool}
     */
    public function render(string $key, array $variables = []): array
    {
        $meta = EmailTemplateRegistry::definition($key);
        $flat = $this->stringifyVariables($variables);
        $template = EmailTemplate::query()->where('key', $key)->first();

        if ($template && $template->isActive()) {
            return [
                'subject' => $this->interpolate($template->subject, $flat),
                'html' => $this->interpolate($template->body_html, $flat),
                'used_db' => true,
            ];
        }

        if ($meta) {
            // Inactive or missing → Blade fallback (legacy views still work).
            $html = View::make($meta['fallback_view'], $this->fallbackViewData($key, $variables))->render();

            return [
                'subject' => $this->interpolate($meta['default_subject'], $flat),
                'html' => $html,
                'used_db' => false,
            ];
        }

        throw new \InvalidArgumentException('Unknown or inactive email template key: '.$key);
    }

    /**
     * @param  string|list<string>  $to
     * @param  array<string, mixed>  $variables
     */
    public function send(string $key, string|array $to, array $variables = []): void
    {
        $this->settings->applyMailConfig();
        $rendered = $this->render($key, $variables);

        Mail::to($to)->send(new TemplateMail($rendered['subject'], $rendered['html']));
    }

    /**
     * @param  array<string, mixed>  $variables
     * @return array<string, string>
     */
    protected function stringifyVariables(array $variables): array
    {
        $out = [];
        foreach ($variables as $key => $value) {
            if (is_bool($value)) {
                $out[$key] = $value ? '1' : '0';
            } elseif (is_scalar($value) || $value === null) {
                $out[$key] = (string) ($value ?? '');
            } else {
                // Non-scalar blocks (pre-built HTML) must already be strings in the payload.
                $out[$key] = is_string($value) ? $value : '';
            }
        }

        return $out;
    }

    /**
     * Replace {{var}} tokens. Unknown tokens become empty string.
     *
     * @param  array<string, string>  $variables
     */
    public function interpolate(string $text, array $variables): string
    {
        return (string) preg_replace_callback(
            '/\{\{\s*([a-zA-Z0-9_.]+)\s*\}\}/',
            function (array $m) use ($variables) {
                $key = $m[1];

                return array_key_exists($key, $variables) ? $variables[$key] : '';
            },
            $text
        );
    }

    /**
     * Data shape expected by legacy Blade fallbacks.
     *
     * @param  array<string, mixed>  $variables
     * @return array<string, mixed>
     */
    protected function fallbackViewData(string $key, array $variables): array
    {
        return match ($key) {
            EmailTemplateRegistry::REGISTRATION_CREDENTIALS => [
                'credentials' => $variables['_credentials'] ?? $variables,
            ],
            EmailTemplateRegistry::REGISTRATION_OTP => [
                'instituteName' => $variables['institute_name'] ?? '',
                'adminName' => $variables['admin_name'] ?? '',
                'otp' => $variables['otp'] ?? '',
                'expiresMinutes' => $variables['expires_minutes'] ?? '15',
            ],
            EmailTemplateRegistry::DEMO_CLIENT_RECEIVED,
            EmailTemplateRegistry::DEMO_CLIENT_CONFIRMED,
            EmailTemplateRegistry::DEMO_CLIENT_DECLINED => [
                'inquiry' => $variables['_inquiry'] ?? null,
                'dateLabel' => $variables['date_label'] ?? '',
                'timeLabel' => $variables['time_label'] ?? '',
            ],
            EmailTemplateRegistry::DEMO_ADMIN_ACTION => [
                'inquiry' => $variables['_inquiry'] ?? null,
                'dateLabel' => $variables['date_label'] ?? '',
                'timeLabel' => $variables['time_label'] ?? '',
                'confirmUrl' => $variables['confirm_url'] ?? '',
                'declineUrl' => $variables['decline_url'] ?? '',
            ],
            default => $variables,
        };
    }

    /**
     * Build template variables for registration credentials mail.
     *
     * @param  array<string, mixed>  $credentials
     * @return array<string, mixed>
     */
    public function variablesForCredentials(array $credentials): array
    {
        $branchLogins = collect($credentials['branches'] ?? [])
            ->filter(fn ($b) => is_array($b) && ! empty($b['manager_email']))
            ->values();

        if ($branchLogins->isEmpty()
            && ! empty($credentials['has_separate_branch_login'])
            && ! empty($credentials['manager_email'])) {
            $branchLogins = collect([[
                'branch_name' => $credentials['branch_name'] ?? 'Branch',
                'manager_name' => $credentials['manager_name'] ?? '',
                'manager_email' => $credentials['manager_email'],
                'manager_password' => $credentials['manager_password'] ?? '',
            ]]);
        }

        $branchHtml = '';
        foreach ($branchLogins as $index => $branchLogin) {
            $titleExtra = $branchLogins->count() > 1 ? ' #'.($index + 1) : '';
            $branchHtml .= '<div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #fffaf0; border: 1px solid #f0e2c4;">'
                .'<p style="margin: 0 0 8px; font-weight: 700; color: #8a6418;">Branch Manager login'.$titleExtra.' (Branch portal)</p>'
                .'<p style="margin: 0;">'
                .'Branch: '.e($branchLogin['branch_name'] ?? 'Branch').'<br>'
                .'Manager: '.e($branchLogin['manager_name'] ?? '').'<br>'
                .'Email / Login ID: <strong>'.e($branchLogin['manager_email'] ?? '').'</strong><br>'
                .'Temporary Password: <strong style="font-family: Consolas, Monaco, monospace;">'.e($branchLogin['manager_password'] ?? '').'</strong><br>'
                .'Login URL: <a href="'.e($credentials['branch_login_url'] ?? '').'" style="color: #8a6418;">'.e($credentials['branch_login_url'] ?? '').'</a>'
                .'</p></div>';
        }

        $forgot = $credentials['forgot_password_url'] ?? route('password.request');
        if (! empty($credentials['is_temporary'])) {
            $security = '<div style="margin: 0 0 8px; padding: 12px 14px; border-radius: 10px; background: #fff5f5; border: 1px solid #f0c9c9;">'
                .'<p style="margin: 0; font-size: 14px; color: #9b2c2c;"><strong>Important:</strong> '
                .'These are <strong>temporary passwords</strong>. On first login you will be asked to set a new password. '
                .'You can also use <a href="'.e($forgot).'" style="color: #9b2c2c;">Forgot password</a> anytime. '
                .'Do not share this email with anyone you do not trust.</p></div>';
        } else {
            $security = '<div style="margin: 0 0 8px; padding: 12px 14px; border-radius: 10px; background: #fff5f5; border: 1px solid #f0c9c9;">'
                .'<p style="margin: 0; font-size: 14px; color: #9b2c2c;"><strong>Important:</strong> '
                .'For your security, please change all passwords right after you log in for the first time. '
                .'Do not share this email with anyone you do not trust.</p></div>';
        }

        return [
            '_credentials' => $credentials,
            'institute_name' => (string) ($credentials['institute_name'] ?? ''),
            'admin_name' => (string) ($credentials['admin_name'] ?? ''),
            'admin_email' => (string) ($credentials['admin_email'] ?? ''),
            'admin_password' => (string) ($credentials['admin_password'] ?? ''),
            'institute_login_url' => (string) ($credentials['institute_login_url'] ?? ''),
            'branch_login_url' => (string) ($credentials['branch_login_url'] ?? ''),
            'forgot_password_url' => (string) $forgot,
            'branch_logins_html' => $branchHtml,
            'security_note_html' => $security,
        ];
    }

    /**
     * Build template variables for the registration OTP mail.
     *
     * @return array<string, mixed>
     */
    public function variablesForOtp(string $instituteName, string $adminName, string $otp, int $expiresMinutes = 15): array
    {
        return [
            'institute_name' => $instituteName,
            'admin_name' => $adminName,
            'otp' => $otp,
            'expires_minutes' => (string) $expiresMinutes,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function variablesForDemoInquiry(
        \App\Models\ProjectInquiry $inquiry,
        string $dateLabel,
        string $timeLabel,
        ?string $confirmUrl = null,
        ?string $declineUrl = null,
        string $meetLabel = 'Google Meet',
    ): array {
        $meet = (string) ($inquiry->google_meet_link ?? '');
        $meetHtml = $meet !== ''
            ? '<p><strong>'.e($meetLabel).':</strong><br><a href="'.e($meet).'">'.e($meet).'</a></p>'
            : '';

        return [
            '_inquiry' => $inquiry,
            'inquiry_code' => (string) ($inquiry->inquiry_code ?? ''),
            'institute_name' => (string) ($inquiry->institute_name ?? ''),
            'contact_person_name' => (string) ($inquiry->contact_person_name ?? ''),
            'email' => (string) ($inquiry->email ?? ''),
            'contact_no' => (string) ($inquiry->contact_no ?? ''),
            'city' => (string) ($inquiry->city ?? ''),
            'date_label' => $dateLabel,
            'time_label' => $timeLabel,
            'timezone' => (string) ($inquiry->timezone ?? ''),
            'google_meet_link' => $meet,
            'google_meet_html' => $meetHtml,
            'confirm_url' => (string) ($confirmUrl ?? ''),
            'decline_url' => (string) ($declineUrl ?? ''),
            'book_demo_url' => route('book-demo'),
        ];
    }
}
