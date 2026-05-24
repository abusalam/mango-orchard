<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Stop-impersonation endpoint. Not gated by `users.impersonate` because
 * during impersonation the *target* user is signed in, and they may not
 * hold the permission — but they (or rather, the original actor) must
 * still be able to exit. The session key `Impersonation::SESSION_KEY` is
 * the source of truth that an impersonation is in flight.
 */
class ImpersonationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function stop(Impersonation $impersonation): RedirectResponse
    {
        if (! $impersonation->isActive()) {
            return redirect()->route('dashboard');
        }

        $original = $impersonation->stop();

        if ($original === null) {
            return redirect()->route('home')
                ->with('status', 'The original signed-in user no longer exists; you have been logged out.');
        }

        return redirect()->route('dashboard')
            ->with('status', "Back to {$original->name}.");
    }
}
