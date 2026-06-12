<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * First-run gate. On a fresh install (setup flag false AND zero users),
 * every request is redirected to the /setup wizard where the first
 * superuser account is created and a site logo can be uploaded.
 *
 * Installs that predate the wizard (users already exist) self-heal: the
 * flag is set on the first request and the count query never runs again.
 */
class RequireSiteSetup
{
    private const array ALLOWED_ROUTES = [
        'setup.show',
        'setup.store',
        'cookies.policy',
        'cookies.required',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $settings = app(Settings::class);

        if ($settings->setupCompleted()) {
            return $next($request);
        }

        if (User::query()->count() > 0) {
            // Pre-wizard install — mark complete so we never count again.
            $settings->set(Settings::SITE_SETUP_COMPLETED, true);

            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}
