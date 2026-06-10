<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboardingComplete
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->hasCompletedOnboarding()) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALWAYS_ALLOWED_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('onboarding.start');
    }

    private const array ALWAYS_ALLOWED_ROUTES = [
        'onboarding.*',
        'logout',
        'home',
        'varieties.index',
        'varieties.show',
        'listings.index',
        'listings.show',
        'events.index',
        'events.show',
        'mpcp.index',
        'gallery.index',
        'gallery.show',
        'preferences.unsubscribe-newsletter',
    ];
}
