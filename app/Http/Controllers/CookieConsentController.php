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
