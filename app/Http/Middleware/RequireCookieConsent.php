<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Block access to any feature that needs cookies (auth, forms, admin, owner
 * CRUD, onboarding) until the visitor has chosen a cookie preference in the
 * banner. Read-only public pages (home, varieties, listings, events,
 * advisories) stay accessible so the visitor can browse before consenting.
 *
 * When a gated route is hit without consent, the request is redirected to
 * the friendly explainer at [`cookies.required`](routes/web.php) with the
 * original URL preserved in a `return` query param. After the visitor picks
 * a preference and the page reloads, the explainer controller forwards them
 * back to where they were going.
 *
 * Mirrors the EnsureOnboardingComplete pattern: an explicit allowlist of
 * route names that stay open to un-consented visitors, everything else is
 * blocked.
 */
class RequireCookieConsent
{
    private const ALWAYS_ALLOWED_ROUTES = [
        // Public browsing — no cookies needed, no forms submitted.
        'home',
        'varieties.index',
        'varieties.show',
        'listings.index',
        'listings.show',
        'events.index',
        'events.show',
        'advisories.index',
        'advisories.show',

        // The explainer page itself, the public cookie policy / preferences
        // page, and the logout endpoint (logout never needs to be
        // re-authorised behind a banner — it's an exit).
        'cookies.required',
        'cookies.policy',
        'logout',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        if ($request->cookie('cookie_consent') !== null) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();
        if (in_array($routeName, self::ALWAYS_ALLOWED_ROUTES, true)) {
            return $next($request);
        }

        // For non-GET requests (form submissions without consent), redirect
        // to the explainer without a return URL — there's nothing meaningful
        // to return to (the form they came from will re-render fresh).
        $params = $request->isMethod('GET')
            ? ['return' => $request->fullUrl()]
            : [];

        return redirect()->route('cookies.required', $params);
    }
}
