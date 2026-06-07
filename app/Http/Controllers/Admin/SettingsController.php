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
            'readonlyMode' => $settings->readonlyMode(),
            'mailEnabled' => $settings->mailEnabled(),
            'mailMangoOrchardEnabled' => (bool) $settings->get(Settings::MAIL_MANGO_ORCHARD_ENABLED, true),
            'mailSchemeMonitoringEnabled' => (bool) $settings->get(Settings::MAIL_SCHEME_MONITORING_ENABLED, true),
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'captcha_enabled' => ['nullable', 'boolean'],
            'captcha_autosolve' => ['nullable', 'boolean'],
            'form_autofill' => ['nullable', 'boolean'],
            'readonly_mode' => ['nullable', 'boolean'],
            'mail_enabled' => ['nullable', 'boolean'],
            'mail_mango_orchard_enabled' => ['nullable', 'boolean'],
            'mail_scheme_monitoring_enabled' => ['nullable', 'boolean'],
        ]);

        $previous = [
            Settings::CAPTCHA_ENABLED => $settings->captchaEnabled(),
            Settings::CAPTCHA_AUTOSOLVE => (bool) $settings->get(Settings::CAPTCHA_AUTOSOLVE, false),
            Settings::FORM_AUTOFILL => $settings->formAutofill(),
            Settings::READONLY_MODE => $settings->readonlyMode(),
            Settings::MAIL_ENABLED => $settings->mailEnabled(),
            Settings::MAIL_MANGO_ORCHARD_ENABLED => (bool) $settings->get(Settings::MAIL_MANGO_ORCHARD_ENABLED, true),
            Settings::MAIL_SCHEME_MONITORING_ENABLED => (bool) $settings->get(Settings::MAIL_SCHEME_MONITORING_ENABLED, true),
        ];

        $next = [
            Settings::CAPTCHA_ENABLED => (bool) ($data['captcha_enabled'] ?? false),
            Settings::CAPTCHA_AUTOSOLVE => (bool) ($data['captcha_autosolve'] ?? false),
            Settings::FORM_AUTOFILL => (bool) ($data['form_autofill'] ?? false),
            Settings::READONLY_MODE => (bool) ($data['readonly_mode'] ?? false),
            Settings::MAIL_ENABLED => (bool) ($data['mail_enabled'] ?? false),
            Settings::MAIL_MANGO_ORCHARD_ENABLED => (bool) ($data['mail_mango_orchard_enabled'] ?? false),
            Settings::MAIL_SCHEME_MONITORING_ENABLED => (bool) ($data['mail_scheme_monitoring_enabled'] ?? false),
        ];

        $settings->set(Settings::CAPTCHA_ENABLED, $next[Settings::CAPTCHA_ENABLED]);
        $settings->set(Settings::CAPTCHA_AUTOSOLVE, $next[Settings::CAPTCHA_AUTOSOLVE]);
        $settings->set(Settings::FORM_AUTOFILL, $next[Settings::FORM_AUTOFILL]);
        $settings->set(Settings::READONLY_MODE, $next[Settings::READONLY_MODE]);
        $settings->set(Settings::MAIL_ENABLED, $next[Settings::MAIL_ENABLED]);
        $settings->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, $next[Settings::MAIL_MANGO_ORCHARD_ENABLED]);
        $settings->set(Settings::MAIL_SCHEME_MONITORING_ENABLED, $next[Settings::MAIL_SCHEME_MONITORING_ENABLED]);

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
