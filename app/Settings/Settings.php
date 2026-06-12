<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class Settings
{
    public const string CAPTCHA_ENABLED = 'captcha_enabled';

    public const string CAPTCHA_AUTOSOLVE = 'captcha_autosolve';

    public const string FORM_AUTOFILL = 'form_autofill';

    public const string READONLY_MODE = 'readonly_mode';

    public const string DEV_BANNER_ENABLED = 'dev_banner_enabled';

    // Branding. The uploaded site logo lives on the public disk under
    // branding/; when unset, <x-site-logo> renders a generated monogram
    // (gradient circle + app-name initials) so a fresh install never shows
    // a broken image.
    public const string SITE_LOGO_PATH = 'site_logo_path';

    // Flipped by the first-run /setup wizard (or self-healed for installs
    // that predate it). While false AND the users table is empty, all
    // traffic redirects to /setup.
    public const string SITE_SETUP_COMPLETED = 'site_setup_completed';

    // Master switch: when off, no notification dispatches the `mail`
    // channel. Database channel still records (TaskStatusChanged /
    // TaskUpdated keep their in-app trail) and the admin Send button
    // refuses for mail-only flows (Newsletter).
    public const string MAIL_ENABLED = 'mail_enabled';

    // Per-module switches. Both must be on (along with MAIL_ENABLED)
    // for the corresponding notification to deliver mail.
    public const string MAIL_MANGO_ORCHARD_ENABLED = 'mail_mango_orchard_enabled';

    public const string MAIL_SCHEME_MONITORING_ENABLED = 'mail_scheme_monitoring_enabled';

    private const string CACHE_KEY = 'app_settings:all';

    /** @var array<string, mixed> */
    public const array DEFAULTS = [
        self::CAPTCHA_ENABLED => false,
        self::CAPTCHA_AUTOSOLVE => false,
        self::FORM_AUTOFILL => false,
        self::READONLY_MODE => false,
        self::DEV_BANNER_ENABLED => false,
        self::SITE_LOGO_PATH => null,
        self::SITE_SETUP_COMPLETED => false,
        // Mail defaults to ON — flipping any of these to false is a
        // deliberate sysadmin act (incident response, staging holds, etc.).
        self::MAIL_ENABLED => true,
        self::MAIL_MANGO_ORCHARD_ENABLED => true,
        self::MAIL_SCHEME_MONITORING_ENABLED => true,
    ];

    public function all(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function (): array {
            $rows = DB::table('settings')->pluck('value', 'key')->all();

            $values = [];
            foreach ($rows as $key => $raw) {
                $values[$key] = $this->decode($raw);
            }

            return array_replace(self::DEFAULTS, $values);
        });
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $all = $this->all();

        if (array_key_exists($key, $all)) {
            return $all[$key];
        }

        return $default ?? (self::DEFAULTS[$key] ?? null);
    }

    public function set(string $key, mixed $value): void
    {
        DB::table('settings')->updateOrInsert(
            ['key' => $key],
            [
                'value' => $this->encode($value),
                'updated_at' => now(),
                'created_at' => now(),
            ],
        );

        $this->forget();
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    public function captchaEnabled(): bool
    {
        return (bool) $this->get(self::CAPTCHA_ENABLED, false);
    }

    public function captchaAutosolve(): bool
    {
        return $this->captchaEnabled() && (bool) $this->get(self::CAPTCHA_AUTOSOLVE, false);
    }

    public function formAutofill(): bool
    {
        return (bool) $this->get(self::FORM_AUTOFILL, false);
    }

    public function readonlyMode(): bool
    {
        return (bool) $this->get(self::READONLY_MODE, false);
    }

    public function devBannerEnabled(): bool
    {
        return (bool) $this->get(self::DEV_BANNER_ENABLED, false);
    }

    public function siteLogoPath(): ?string
    {
        $path = $this->get(self::SITE_LOGO_PATH);

        return is_string($path) && $path !== '' ? $path : null;
    }

    public function siteLogoUrl(): ?string
    {
        $path = $this->siteLogoPath();

        if ($path === null || ! Storage::disk('public')->exists($path)) {
            return null;
        }

        return Storage::disk('public')->url($path);
    }

    public function setupCompleted(): bool
    {
        return (bool) $this->get(self::SITE_SETUP_COMPLETED, false);
    }

    /**
     * Master mail kill-switch. Notifications check this AND their module
     * flag — if either is false, the mail channel is dropped from `via()`.
     */
    public function mailEnabled(): bool
    {
        return (bool) $this->get(self::MAIL_ENABLED, true);
    }

    public function mailEnabledForMangoOrchard(): bool
    {
        return $this->mailEnabled() && (bool) $this->get(self::MAIL_MANGO_ORCHARD_ENABLED, true);
    }

    public function mailEnabledForSchemeMonitoring(): bool
    {
        return $this->mailEnabled() && (bool) $this->get(self::MAIL_SCHEME_MONITORING_ENABLED, true);
    }

    private function encode(mixed $value): string
    {
        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    private function decode(?string $raw): mixed
    {
        if ($raw === null) {
            return null;
        }

        try {
            return json_decode($raw, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return $raw;
        }
    }
}
