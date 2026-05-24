<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Permissions;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class SettingsController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::SETTINGS_MANAGE]),
        ];
    }

    public function edit(Settings $settings): View
    {
        return view('admin.settings.edit', [
            'captchaEnabled' => $settings->captchaEnabled(),
            'captchaAutosolve' => (bool) $settings->get(Settings::CAPTCHA_AUTOSOLVE, false),
            'formAutofill' => $settings->formAutofill(),
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'captcha_enabled' => ['nullable', 'boolean'],
            'captcha_autosolve' => ['nullable', 'boolean'],
            'form_autofill' => ['nullable', 'boolean'],
        ]);

        $previous = [
            Settings::CAPTCHA_ENABLED => $settings->captchaEnabled(),
            Settings::CAPTCHA_AUTOSOLVE => (bool) $settings->get(Settings::CAPTCHA_AUTOSOLVE, false),
            Settings::FORM_AUTOFILL => $settings->formAutofill(),
        ];

        $next = [
            Settings::CAPTCHA_ENABLED => (bool) ($data['captcha_enabled'] ?? false),
            Settings::CAPTCHA_AUTOSOLVE => (bool) ($data['captcha_autosolve'] ?? false),
            Settings::FORM_AUTOFILL => (bool) ($data['form_autofill'] ?? false),
        ];

        $settings->set(Settings::CAPTCHA_ENABLED, $next[Settings::CAPTCHA_ENABLED]);
        $settings->set(Settings::CAPTCHA_AUTOSOLVE, $next[Settings::CAPTCHA_AUTOSOLVE]);
        $settings->set(Settings::FORM_AUTOFILL, $next[Settings::FORM_AUTOFILL]);

        $changed = array_keys(array_filter(
            $next,
            fn ($value, $key) => $value !== $previous[$key],
            ARRAY_FILTER_USE_BOTH,
        ));

        if ($changed !== []) {
            app(Telemetry::class)->record(
                Telemetry::SETTINGS_UPDATED,
                context: ['changed' => $changed, 'values' => $next],
            );
        }

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Settings saved.');
    }
}
