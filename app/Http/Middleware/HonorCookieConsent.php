<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Strict cookie gate: until the visitor clicks a banner button, NO cookies
 * are written back. The cookie banner itself is the only thing the visitor
 * can interact with — its choice is stored client-side via JS, so once it's
 * set the next request carries it and this middleware becomes a no-op.
 *
 * Consequence: login / register / any form submission requires the visitor
 * to consent first (without a session cookie, CSRF validation can never
 * succeed). This is intentional — the banner JS reloads the page after a
 * choice is recorded so the form re-renders with a fresh session.
 *
 * Authenticated users with no consent cookie (e.g. they hit "Reset cookie
 * preferences" while logged in) keep working until the session cookie
 * already on their browser expires naturally — we don't actively renew it,
 * but we don't actively destroy it either.
 */
class HonorCookieConsent
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->cookie('cookie_consent') !== null) {
            return $response;
        }

        $this->removeCookie($response, (string) config('session.cookie'));
        $this->removeCookie($response, 'XSRF-TOKEN');

        return $response;
    }

    private function removeCookie(Response $response, string $name): void
    {
        $response->headers->removeCookie($name, '/');

        $domain = config('session.domain');
        if (is_string($domain) && $domain !== '') {
            $response->headers->removeCookie($name, '/', $domain);
        }
    }
}
