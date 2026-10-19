<?php

namespace App\Support;

/**
 * Fixed email template keys, variable docs, and default subject/body.
 * Triggers stay in code; copy is editable in admin (email_templates).
 */
class EmailTemplateRegistry
{
    public const REGISTRATION_CREDENTIALS = 'registration.credentials';

    public const REGISTRATION_OTP = 'registration.otp';

    public const DEMO_CLIENT_RECEIVED = 'demo.client_received';

    public const DEMO_ADMIN_ACTION = 'demo.admin_action';

    public const DEMO_CLIENT_CONFIRMED = 'demo.client_confirmed';

    public const DEMO_CLIENT_DECLINED = 'demo.client_declined';

    /**
     * @return array<string, array{
     *     name: string,
     *     description: string,
     *     fallback_view: string,
     *     variables: array<string, string>,
     *     default_subject: string,
     *     default_body: string
     * }>
     */
    public static function all(): array
    {
        return [
            self::REGISTRATION_CREDENTIALS => [
                'name' => 'Institute registration credentials',
                'description' => 'Sent after OTP verify or admin convert — login IDs and temporary passwords.',
                'fallback_view' => 'emails.registration.credentials',
                'variables' => [
                    'institute_name' => 'Institute name',
                    'admin_name' => 'Institute admin name',
                    'admin_email' => 'Admin login email',
                    'admin_password' => 'Admin temporary password',
                    'institute_login_url' => 'Institute CRM login URL',
                    'branch_login_url' => 'Branch portal login URL',
                    'branch_logins_html' => 'Pre-built HTML block for branch manager login(s)',
                    'security_note_html' => 'Pre-built security / temp-password note HTML',
                    'forgot_password_url' => 'Forgot password page URL',
                ],
                'default_subject' => 'Your Class Flow login details — {{institute_name}}',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px; background: #f7f8f7;">
  <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px 24px; border: 1px solid #e6ebe9;">
    <h2 style="margin: 0 0 8px; color: #0f5c4d;">Welcome to Class Flow</h2>
    <p style="margin: 0 0 18px; color: #5b6661;">
      Your institute <strong style="color: #1f2926;">{{institute_name}}</strong>
      is ready. Below are the logins created for you.
    </p>
    <div style="margin-bottom: 18px; padding: 14px 16px; border-radius: 10px; background: #f3faf7; border: 1px solid #d5ebe3;">
      <p style="margin: 0 0 8px; font-weight: 700; color: #0f5c4d;">Institute Admin login (Web CRM)</p>
      <p style="margin: 0;">
        Name: {{admin_name}}<br>
        Email / Login ID: <strong>{{admin_email}}</strong><br>
        Temporary Password: <strong style="font-family: Consolas, Monaco, monospace;">{{admin_password}}</strong><br>
        Login URL: <a href="{{institute_login_url}}" style="color: #0f5c4d;">{{institute_login_url}}</a>
      </p>
    </div>
    {{branch_logins_html}}
    {{security_note_html}}
    <p style="margin: 18px 0 0; color: #5b6661; font-size: 13px;">
      If you did not expect this email, ignore it or contact Class Flow support.
    </p>
    <p style="margin: 12px 0 0; font-weight: 700; color: #0f5c4d;">Class Flow</p>
  </div>
</div>
HTML,
            ],
            self::REGISTRATION_OTP => [
                'name' => 'Institute registration OTP',
                'description' => 'Sent right after registration, and again on resend — 6-digit email verification code.',
                'fallback_view' => 'emails.registration.otp',
                'variables' => [
                    'institute_name' => 'Institute name',
                    'admin_name' => 'Institute admin name',
                    'otp' => '6-digit verification code',
                    'expires_minutes' => 'Minutes until the code expires',
                ],
                'default_subject' => 'Your Class Flow verification code: {{otp}}',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, Helvetica, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px; background: #f7f8f7;">
  <div style="max-width: 560px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 28px 24px; border: 1px solid #e6ebe9;">
    <h2 style="margin: 0 0 8px; color: #0f5c4d;">Verify your email</h2>
    <p style="margin: 0 0 18px; color: #5b6661;">
      Hi {{admin_name}}, use the code below to verify
      <strong style="color: #1f2926;">{{institute_name}}</strong> and activate your Class Flow trial.
    </p>
    <div style="margin-bottom: 18px; padding: 20px 16px; border-radius: 10px; background: #f3faf7; border: 1px solid #d5ebe3; text-align: center;">
      <p style="margin: 0 0 10px; font-weight: 700; color: #0f5c4d; font-size: 13px; text-transform: uppercase; letter-spacing: 0.08em;">Your verification code</p>
      <div style="font-family: Consolas, Monaco, monospace; font-size: 34px; font-weight: 700; color: #0f5c4d; letter-spacing: 10px;">{{otp}}</div>
    </div>
    <div style="margin: 0 0 8px; padding: 12px 14px; border-radius: 10px; background: #fff5f5; border: 1px solid #f0c9c9;">
      <p style="margin: 0; font-size: 14px; color: #9b2c2c;">
        <strong>Important:</strong> This code expires in {{expires_minutes}} minutes.
        Do not share it with anyone — Class Flow staff will never ask for it.
      </p>
    </div>
    <p style="margin: 18px 0 0; color: #5b6661; font-size: 13px;">
      If you did not request this, you can ignore this email.
    </p>
    <p style="margin: 12px 0 0; font-weight: 700; color: #0f5c4d;">Class Flow</p>
  </div>
</div>
HTML,
            ],
            self::DEMO_CLIENT_RECEIVED => [
                'name' => 'Demo — client request received',
                'description' => 'Sent to the client when they book a demo.',
                'fallback_view' => 'emails.demo.client-received',
                'variables' => [
                    'inquiry_code' => 'Inquiry code',
                    'institute_name' => 'Institute / coaching name',
                    'date_label' => 'Preferred date label',
                    'time_label' => 'Preferred time label',
                    'timezone' => 'Timezone',
                ],
                'default_subject' => 'We received your Class Flow demo request',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
  <h2 style="margin-bottom: 8px; color: #0f5c4d;">We received your demo request</h2>
  <p>Thanks for booking a demo with Class Flow. Our team will review this slot and confirm shortly.</p>
  <p>
    <strong>Inquiry Code:</strong> {{inquiry_code}}<br>
    <strong>Institute:</strong> {{institute_name}}<br>
    <strong>Preferred Date:</strong> {{date_label}}<br>
    <strong>Preferred Time:</strong> {{time_label}}<br>
    <strong>Timezone:</strong> {{timezone}}
  </p>
  <p style="color: #5b6661;">You will get another email once the demo is confirmed by our team, with the Google Meet link.</p>
  <p>Class Flow</p>
</div>
HTML,
            ],
            self::DEMO_ADMIN_ACTION => [
                'name' => 'Demo — admin confirm / decline',
                'description' => 'Sent to platform demo notification email with Yes/No links.',
                'fallback_view' => 'emails.demo.admin-action',
                'variables' => [
                    'inquiry_code' => 'Inquiry code',
                    'institute_name' => 'Institute name',
                    'contact_person_name' => 'Contact person',
                    'email' => 'Client email',
                    'contact_no' => 'Client phone',
                    'city' => 'City',
                    'date_label' => 'Demo date',
                    'time_label' => 'Demo time',
                    'google_meet_link' => 'Meet link (may be empty)',
                    'google_meet_html' => 'Optional Meet HTML block',
                    'confirm_url' => 'Confirm (Yes) signed URL',
                    'decline_url' => 'Decline (No) signed URL',
                ],
                'default_subject' => 'New demo request — please confirm',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
  <h2 style="margin-bottom: 8px; color: #0f5c4d;">New demo — please confirm</h2>
  <p>A client booked a demo slot. Are you ready to take this demo?</p>
  <p>
    <strong>Inquiry Code:</strong> {{inquiry_code}}<br>
    <strong>Institute:</strong> {{institute_name}}<br>
    <strong>Contact Person:</strong> {{contact_person_name}}<br>
    <strong>Email:</strong> {{email}}<br>
    <strong>Phone:</strong> {{contact_no}}<br>
    <strong>City:</strong> {{city}}<br>
    <strong>Demo Date:</strong> {{date_label}}<br>
    <strong>Demo Time:</strong> {{time_label}}
  </p>
  {{google_meet_html}}
  <p style="margin: 28px 0 12px;">
    <a href="{{confirm_url}}" style="display:inline-block;background:#0f5c4d;color:#fff;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:700;margin-right:10px;">Yes — Confirm Demo</a>
    <a href="{{decline_url}}" style="display:inline-block;background:#fff;color:#b42318;text-decoration:none;padding:12px 22px;border-radius:8px;font-weight:700;border:2px solid #b42318;">No — Decline</a>
  </p>
  <p style="font-size: 13px; color: #5b6661;">Yes → client gets final confirmation + Meet link.<br>No → slot is released and client is notified.</p>
</div>
HTML,
            ],
            self::DEMO_CLIENT_CONFIRMED => [
                'name' => 'Demo — client confirmed',
                'description' => 'Sent to the client when admin confirms the demo.',
                'fallback_view' => 'emails.demo.client-confirmed',
                'variables' => [
                    'inquiry_code' => 'Inquiry code',
                    'institute_name' => 'Institute name',
                    'date_label' => 'Demo date',
                    'time_label' => 'Demo time',
                    'timezone' => 'Timezone',
                    'google_meet_link' => 'Meet link',
                    'google_meet_html' => 'Optional Meet HTML block',
                ],
                'default_subject' => 'Your Class Flow demo is confirmed',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
  <h2 style="margin-bottom: 8px; color: #0f5c4d;">Your demo is confirmed</h2>
  <p>Great news — our team has confirmed your Class Flow demo. Please join using the Google Meet link below at the scheduled time.</p>
  <p>
    <strong>Inquiry Code:</strong> {{inquiry_code}}<br>
    <strong>Institute:</strong> {{institute_name}}<br>
    <strong>Demo Date:</strong> {{date_label}}<br>
    <strong>Demo Time:</strong> {{time_label}}<br>
    <strong>Timezone:</strong> {{timezone}}
  </p>
  {{google_meet_html}}
  <p>We look forward to speaking with you.</p>
  <p>Class Flow</p>
</div>
HTML,
            ],
            self::DEMO_CLIENT_DECLINED => [
                'name' => 'Demo — client declined',
                'description' => 'Sent to the client when admin declines the demo.',
                'fallback_view' => 'emails.demo.client-declined',
                'variables' => [
                    'inquiry_code' => 'Inquiry code',
                    'date_label' => 'Requested date',
                    'time_label' => 'Requested time',
                    'book_demo_url' => 'Book another demo URL',
                ],
                'default_subject' => 'Update on your Class Flow demo request',
                'default_body' => <<<'HTML'
<div style="font-family: Arial, sans-serif; color: #1f2926; line-height: 1.6; padding: 24px;">
  <h2 style="margin-bottom: 8px; color: #0f5c4d;">Update on your demo request</h2>
  <p>Thank you for your interest in Class Flow. Unfortunately we could not confirm the slot below. Please book another time that works for you.</p>
  <p>
    <strong>Inquiry Code:</strong> {{inquiry_code}}<br>
    <strong>Requested Date:</strong> {{date_label}}<br>
    <strong>Requested Time:</strong> {{time_label}}
  </p>
  <p><a href="{{book_demo_url}}" style="color: #0f5c4d; font-weight: 700;">Book another demo slot</a></p>
  <p>Class Flow</p>
</div>
HTML,
            ],
        ];
    }

    /**
     * @return list<string>
     */
    public static function keys(): array
    {
        return array_keys(self::all());
    }

    /**
     * @return array<string, mixed>|null
     */
    public static function definition(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }
}
