<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Permissions;
use App\Roles;
use App\Telemetry\Telemetry;
use Illuminate\Support\Facades\Auth;
use RuntimeException;

/**
 * Tracks impersonation state across the session. Single source of truth for
 * "who is the real signed-in user" (recovered via `originalUser()`) while
 * Auth::user() returns the *target* of the impersonation.
 */
class Impersonation
{
    public const string SESSION_KEY = 'impersonator_id';

    public const string SESSION_REASON_KEY = 'impersonator_reason';

    public function isActive(): bool
    {
        return session()->has(self::SESSION_KEY);
    }

    public function originalUser(): ?User
    {
        if (! $this->isActive()) {
            return null;
        }

        return User::find(session(self::SESSION_KEY));
    }

    /**
     * @param  'user'|'role'  $reason  Why the impersonation was started — picked
     *                                 directly by id, or chosen as "any user of
     *                                 role X". Surfaced in the banner.
     */
    public function start(User $target, string $reason = 'user'): void
    {
        if ($this->isActive()) {
            throw new RuntimeException('Already impersonating — stop the current session first.');
        }

        $actor = Auth::user();
        if ($actor === null) {
            throw new RuntimeException('You must be signed in to start impersonating.');
        }

        $this->assertCanImpersonate($actor, $target);

        $actorId = $actor->id;

        Auth::login($target);
        session([
            self::SESSION_KEY => $actorId,
            self::SESSION_REASON_KEY => $reason,
        ]);

        app(Telemetry::class)->record(
            Telemetry::IMPERSONATION_STARTED,
            subject: $target,
            context: [
                'actor_id' => $actorId,
                'target_id' => $target->id,
                'target_email' => $target->email,
                'reason' => $reason,
            ],
            userId: $actorId,
        );
    }

    public function stop(): ?User
    {
        if (! $this->isActive()) {
            return null;
        }

        $originalId = (int) session(self::SESSION_KEY);
        $reason = (string) session(self::SESSION_REASON_KEY, 'user');
        $impersonatedId = Auth::id();

        $original = User::find($originalId);
        if ($original === null) {
            // Original user was deleted while we were impersonating —
            // log the orphan out cleanly.
            Auth::logout();
            session()->forget([self::SESSION_KEY, self::SESSION_REASON_KEY]);

            return null;
        }

        Auth::login($original);
        session()->forget([self::SESSION_KEY, self::SESSION_REASON_KEY]);

        app(Telemetry::class)->record(
            Telemetry::IMPERSONATION_STOPPED,
            subject: $original,
            context: [
                'actor_id' => $original->id,
                'target_id' => $impersonatedId,
                'reason' => $reason,
            ],
            userId: $original->id,
        );

        return $original;
    }

    /**
     * Pick the first non-self user that holds a given role. Used by the
     * "impersonate any [role]" shortcut.
     */
    public function firstUserWithRole(string $roleName, User $excluding): ?User
    {
        return User::role($roleName)
            ->where('id', '!=', $excluding->id)
            ->orderBy('id')
            ->first();
    }

    private function assertCanImpersonate(User $actor, User $target): void
    {
        if ($actor->id === $target->id) {
            throw new RuntimeException("You can't impersonate yourself.");
        }

        if (! $actor->can(Permissions::USERS_IMPERSONATE)) {
            throw new RuntimeException('You do not have permission to impersonate.');
        }

        // Privilege-escalation guard: only a superuser may impersonate another
        // superuser. A regular impersonator hijacking a superuser session would
        // promote themselves to superuser permissions wholesale.
        if ($target->hasRole(Roles::SUPERUSER) && ! $actor->hasRole(Roles::SUPERUSER)) {
            throw new RuntimeException('You cannot impersonate a superuser.');
        }
    }
}
