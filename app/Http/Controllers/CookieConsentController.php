<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Explainer page shown when a visitor without a cookie preference tries to
 * reach a feature that needs cookies (auth, forms, admin, owner CRUD, etc).
 * Tells them what's blocked and why, with the consent banner already on
 * screen via the layout so they can choose and move on.
 *
 * After the banner JS reloads the page with the new `cookie_consent`
 * cookie set, this controller forwards the visitor back to wherever they
 * were originally heading (preserved in the `?return=` query param).
 */
class CookieConsentController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $return = $this->safeReturnUrl($request);

        if ($request->cookie('cookie_consent') !== null) {
            return redirect($return ?? route('home'));
        }

        return view('cookies-required', ['return' => $return]);
    }

    /**
     * Public cookie policy + preferences page (modelled on
     * https://www.india.gov.in/cookies). Lists every cookie the site
     * sets, grouped into Essential and Optional, with a toggle for the
     * Optional row that maps to the visitor's `cookie_consent` choice.
     */
    public function policy(Request $request): View
    {
        $current = $request->cookie('cookie_consent');

        $sessionCookie = (string) config('session.cookie');
        $sessionLifetime = (int) config('session.lifetime');

        return view('cookies', [
            'current' => $current,
            'analyticsOptedIn' => $current === 'all',
            'essential' => [
                [
                    'name' => $sessionCookie,
                    'purpose' => 'Manages your sign-in session and CSRF protection for forms. Deleted when you log out or your browser closes.',
                    'lifetime' => "Session ({$sessionLifetime} minutes idle)",
                ],
                [
                    'name' => 'XSRF-TOKEN',
                    'purpose' => 'Cross-site request forgery token paired with the session cookie so the server can verify that form submissions come from this site.',
                    'lifetime' => 'Session',
                ],
                [
                    'name' => 'cookie_consent',
                    'purpose' => 'Remembers your cookie choice on this page so we do not re-prompt on every visit.',
                    'lifetime' => '1 year',
                ],
            ],
            'optional' => [
                [
                    'name' => 'Activity analytics',
                    'purpose' => 'When enabled, records your actions (creating listings, updating settings, etc.) in the admin activity feed for moderation and audit. Sign-in and administrative account-access events are always recorded as a security audit, regardless of this choice.',
                    'lifetime' => 'Server-side log',
                ],
            ],
        ]);
    }

    /**
     * Only allow `?return=` to point at the same host — otherwise it's an
     * open-redirect waiting to happen. Returns null when the param is
     * missing or off-host.
     */
    private function safeReturnUrl(Request $request): ?string
    {
        $return = $request->query('return');
        if (! is_string($return) || $return === '') {
            return null;
        }

        $parsed = parse_url($return);
        if ($parsed === false) {
            return null;
        }

        $host = $parsed['host'] ?? null;
        if ($host !== null && $host !== $request->getHost()) {
            return null;
        }

        return $return;
    }
}
