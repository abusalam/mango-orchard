<?php

declare(strict_types=1);

namespace App\Settings;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class Settings
{
    public const string CAPTCHA_ENABLED = 'captcha_enabled';

    public const string CAPTCHA_AUTOSOLVE = 'captcha_autosolve';

    public const string FORM_AUTOFILL = 'form_autofill';

    private const string CACHE_KEY = 'app_settings:all';

    /** @var array<string, mixed> */
    public const array DEFAULTS = [
        self::CAPTCHA_ENABLED => false,
        self::CAPTCHA_AUTOSOLVE => false,
        self::FORM_AUTOFILL => false,
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
