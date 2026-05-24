<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Permissions;
use App\Settings\Settings;
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
        ]);
    }

    public function update(Request $request, Settings $settings): RedirectResponse
    {
        $data = $request->validate([
            'captcha_enabled' => ['nullable', 'boolean'],
            'captcha_autosolve' => ['nullable', 'boolean'],
        ]);

        $settings->set(Settings::CAPTCHA_ENABLED, (bool) ($data['captcha_enabled'] ?? false));
        $settings->set(Settings::CAPTCHA_AUTOSOLVE, (bool) ($data['captcha_autosolve'] ?? false));

        return redirect()
            ->route('admin.settings.edit')
            ->with('status', 'Settings saved.');
    }
}
