<?php

declare(strict_types=1);

namespace App\Telemetry;

use App\Models\TelemetryEvent;
use App\Models\User;
use App\Services\Impersonation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Throwable;

class Telemetry
{
    private static bool $suppressed = false;

    public const string AUTH_REGISTERED = 'auth.registered';

    public const string AUTH_LOGIN_SUCCEEDED = 'auth.login.succeeded';

    public const string AUTH_LOGIN_FAILED = 'auth.login.failed';

    public const string AUTH_LOGOUT = 'auth.logout';

    public const string AUTH_PASSWORD_RESET_REQUESTED = 'auth.password.reset.requested';

    public const string AUTH_PASSWORD_RESET_COMPLETED = 'auth.password.reset.completed';

    public const string AUTH_CAPTCHA_FAILED = 'auth.captcha.failed';

    public const string ONBOARDING_PROFILE_SAVED = 'onboarding.profile.saved';

    public const string ONBOARDING_PREFERENCES_SAVED = 'onboarding.preferences.saved';

    public const string ONBOARDING_COMPLETED = 'onboarding.completed';

    public const string PREFERENCES_UPDATED = 'preferences.updated';

    public const string VARIETY_CREATED = 'variety.created';

    public const string VARIETY_UPDATED = 'variety.updated';

    public const string VARIETY_DELETED = 'variety.deleted';

    public const string ROLE_CREATED = 'role.created';

    public const string ROLE_UPDATED = 'role.updated';

    public const string ROLE_DELETED = 'role.deleted';

    public const string USER_ROLES_UPDATED = 'user.roles.updated';

    public const string ROLE_APPLICATION_SUBMITTED = 'role_application.submitted';

    public const string ROLE_APPLICATION_CANCELLED = 'role_application.cancelled';

    public const string ROLE_APPLICATION_APPROVED = 'role_application.approved';

    public const string ROLE_APPLICATION_REJECTED = 'role_application.rejected';

    public const string IMPERSONATION_STARTED = 'impersonation.started';

    public const string IMPERSONATION_STOPPED = 'impersonation.stopped';

    public const string ROLE_DELEGATED = 'role.delegated';

    public const string ROLE_DELEGATION_REVOKED = 'role.delegation.revoked';

    public const string SETTINGS_UPDATED = 'settings.updated';

    public const string LISTING_CREATED = 'listing.created';

    public const string LISTING_UPDATED = 'listing.updated';

    public const string LISTING_DELETED = 'listing.deleted';

    public const string EVENT_CREATED = 'event.created';

    public const string EVENT_UPDATED = 'event.updated';

    public const string EVENT_DELETED = 'event.deleted';

    public const string ADVISORY_CREATED = 'advisory.created';

    public const string ADVISORY_UPDATED = 'advisory.updated';

    public const string ADVISORY_DELETED = 'advisory.deleted';

    /**
     * Record a telemetry event. Auto-populates user, ip and user agent from
     * the current request/auth context; callers only need to pass the event
     * name (one of the constants above) plus an optional subject + context.
     *
     * When the current session is in an impersonation, `impersonator_id`
     * and `impersonator_email` are added to the context automatically so
     * the audit trail shows the REAL actor behind any action — without
     * every call site having to remember to add them.
     */
    public function record(
        string $event,
        ?Model $subject = null,
        array $context = [],
        ?int $userId = null,
    ): ?TelemetryEvent {
        if (self::$suppressed) {
            return null;
        }

        $request = request();

        return TelemetryEvent::create([
            'event' => $event,
            'user_id' => $userId ?? Auth::id(),
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $request?->ip(),
            'user_agent' => str($request?->userAgent() ?? '')->limit(497)->toString() ?: null,
            'session_id' => $this->currentSessionId(),
            'context' => $this->mergeImpersonatorContext($context),
            'occurred_at' => now(),
        ]);
    }

    /**
     * Capture the current session ID for audit correlation, guarded against
     * CLI / queue contexts where no session is bound (model observers fire
     * from seeders too).
     */
    private function currentSessionId(): ?string
    {
        try {
            $id = session()->getId();

            // Defensive: extreme custom session IDs could exceed the 64-char
            // column. Truncate rather than throw.
            return $id ? str($id)->limit(60, '')->toString() : null;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * Augment the caller-supplied context with the impersonator's id + email
     * when an impersonation session is active. Returns null for a fully-empty
     * context (preserving the model column's nullability).
     *
     * Guarded against CLI / queue contexts where no session is bound — those
     * fire telemetry too (model observers in seeders, etc.) and shouldn't
     * crash because the session facade is unavailable.
     */
    private function mergeImpersonatorContext(array $context): ?array
    {
        try {
            if (session()->has(Impersonation::SESSION_KEY)) {
                $impersonatorId = (int) session(Impersonation::SESSION_KEY);
                $impersonator = User::find($impersonatorId);
                $context = array_merge($context, [
                    'impersonator_id' => $impersonatorId,
                    'impersonator_email' => $impersonator?->email,
                ]);
            }
        } catch (Throwable) {
            // No session bound (CLI / queue / artisan) — nothing to add.
        }

        return $context === [] ? null : $context;
    }

    /**
     * Run a callback with telemetry recording temporarily disabled. Useful
     * for infrastructural code paths (seeders, imports) that shouldn't
     * pollute the activity feed with synthetic events.
     */
    public static function withoutRecording(callable $callback): mixed
    {
        $previous = self::$suppressed;
        self::$suppressed = true;
        try {
            return $callback();
        } finally {
            self::$suppressed = $previous;
        }
    }
}
