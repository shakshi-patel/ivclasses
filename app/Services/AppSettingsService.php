<?php

namespace App\Services;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class AppSettingsService
{
    protected string $path = 'app-settings.json';

    /** @var array<string, mixed>|null */
    protected ?array $cache = null;

    /** Keys that must never be echoed back to forms. */
    protected array $secretKeys = [
        'mail.password',
        'google.client_secret',
    ];

    /**
     * Admin value first, otherwise .env / config fallback.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $stored = $this->all();

        if (array_key_exists($key, $stored) && $stored[$key] !== null && $stored[$key] !== '') {
            return $stored[$key];
        }

        return $this->envFallback($key, $default);
    }

    public function has(string $key): bool
    {
        $stored = $this->all();

        return array_key_exists($key, $stored)
            && $stored[$key] !== null
            && $stored[$key] !== '';
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        if ($this->cache !== null) {
            return $this->cache;
        }

        if (!Storage::disk('local')->exists($this->path)) {
            return $this->cache = [];
        }

        try {
            $decoded = json_decode(
                Crypt::decryptString(Storage::disk('local')->get($this->path)),
                true
            );

            return $this->cache = is_array($decoded) ? $decoded : [];
        } catch (\Throwable $e) {
            report($e);

            return $this->cache = [];
        }
    }

    /**
     * Merge and save. Empty secret fields keep the previous value.
     *
     * @param  array<string, mixed>  $input
     */
    public function put(array $input): void
    {
        $current = $this->all();

        foreach ($input as $key => $value) {
            if (in_array($key, $this->secretKeys, true)) {
                if ($value === null || $value === '') {
                    continue;
                }
            }

            if ($value === null) {
                continue;
            }

            $current[$key] = is_string($value) ? trim($value) : $value;
        }

        Storage::disk('local')->put(
            $this->path,
            Crypt::encryptString(json_encode($current, JSON_UNESCAPED_UNICODE))
        );

        $this->cache = $current;
    }

    public function forget(string $key): void
    {
        $current = $this->all();
        unset($current[$key]);

        Storage::disk('local')->put(
            $this->path,
            Crypt::encryptString(json_encode($current, JSON_UNESCAPED_UNICODE))
        );

        $this->cache = $current;
    }

    public function isSecret(string $key): bool
    {
        return in_array($key, $this->secretKeys, true);
    }

    public function secretIsSet(string $key): bool
    {
        return $this->has($key) || filled($this->envFallback($key));
    }

    /**
     * Always computed from the app — never stored / never free-edited.
     */
    public function googleRedirectUri(): string
    {
        return url('/admin/google-calendar/callback');
    }

    public function applyMailConfig(): void
    {
        $host = $this->get('mail.host');
        $port = $this->get('mail.port');
        $username = $this->get('mail.username');
        $password = $this->get('mail.password');
        $encryption = $this->get('mail.encryption');
        $fromAddress = $this->get('mail.from_address');
        $fromName = $this->get('mail.from_name');

        if (filled($host)) {
            Config::set('mail.default', 'smtp');
            Config::set('mail.mailers.smtp.host', $host);
        }

        if (filled($port)) {
            Config::set('mail.mailers.smtp.port', (int) $port);
        }

        if (filled($username)) {
            Config::set('mail.mailers.smtp.username', $username);
        }

        if (filled($password)) {
            Config::set('mail.mailers.smtp.password', $password);
        }

        if (filled($encryption)) {
            Config::set('mail.mailers.smtp.encryption', $encryption);
        } elseif (array_key_exists('mail.encryption', $this->all())) {
            Config::set('mail.mailers.smtp.encryption', null);
        }

        if (filled($fromAddress)) {
            Config::set('mail.from.address', $fromAddress);
        }

        if (filled($fromName)) {
            Config::set('mail.from.name', $fromName);
        }

        $notify = $this->get('demo.notification_email');
        if (filled($notify)) {
            Config::set('demo.notification_email', $notify);
        }
    }

    public function applyGoogleConfig(): void
    {
        $clientId = $this->get('google.client_id');
        $clientSecret = $this->get('google.client_secret');
        $calendarId = $this->get('google.calendar_id', 'primary');

        if (filled($clientId)) {
            Config::set('services.google.client_id', $clientId);
        }

        if (filled($clientSecret)) {
            Config::set('services.google.client_secret', $clientSecret);
        }

        Config::set('services.google.redirect', $this->googleRedirectUri());
        Config::set('services.google.calendar_id', $calendarId ?: 'primary');
    }

    protected function envFallback(string $key, mixed $default = null): mixed
    {
        return match ($key) {
            'mail.host' => config('mail.mailers.smtp.host', $default),
            'mail.port' => config('mail.mailers.smtp.port', $default),
            'mail.encryption' => config('mail.mailers.smtp.encryption', $default),
            'mail.username' => config('mail.mailers.smtp.username', $default),
            'mail.password' => config('mail.mailers.smtp.password', $default),
            'mail.from_address' => config('mail.from.address', $default),
            'mail.from_name' => config('mail.from.name', $default),
            'demo.notification_email' => config('demo.notification_email', $default),
            'google.client_id' => config('services.google.client_id', $default),
            'google.client_secret' => config('services.google.client_secret', $default),
            'google.calendar_id' => config('services.google.calendar_id', $default ?? 'primary'),
            default => $default,
        };
    }
}
