<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Roles;
use App\Settings\Settings;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Site-wide read-only freeze. When the `readonly_mode` setting is on, any
 * mutating request (POST / PUT / PATCH / DELETE) is rejected with a 403
 * unless one of the carve-outs applies:
 *
 *   - The actor holds the superuser role — they need to be able to turn
 *     the freeze back off and fix whatever caused it.
 *   - The route is on the always-allowed list: sign in / out, password
 *     reset, the admin settings update itself, and the
 *     impersonation stop endpoint (so a stuck "acting as" session can
 *     always be exited).
 *
 * Read traffic (GET / HEAD / OPTIONS) is never gated.
 */
class EnforceReadonlyMode
{
    private const ALWAYS_ALLOWED_ROUTES = [
        'login',
        'logout',
        'password.email',
        'password.store',
        'password.update',
        'verification.send',
        'admin.settings.update',
        'impersonate.stop',
    ];

    /**
     * Laravel Breeze's auth POSTs share a URI with their named GET
     * counterpart but the POST verb itself isn't given a route name,
     * so we match by path as a backstop. Note: `register` is NOT
     * listed — creating a new user account is a write and is blocked
     * during read-only mode regardless of role. Sign-in itself stays
     * open at the middleware level so the request reaches
     * AuthenticatedSessionController, which enforces "superuser only"
     * after credentials are verified.
     */
    private const ALWAYS_ALLOWED_PATHS = [
        'login',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if (! app(Settings::class)->readonlyMode()) {
            return $next($request);
        }

        if (in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS'], true)) {
            return $next($request);
        }

        if (Auth::user()?->hasRole(Roles::SUPERUSER)) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, self::ALWAYS_ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        if (in_array($request->path(), self::ALWAYS_ALLOWED_PATHS, true)) {
            return $next($request);
        }

        abort(403, 'The site is in read-only mode. Changes are paused.');
    }
}
