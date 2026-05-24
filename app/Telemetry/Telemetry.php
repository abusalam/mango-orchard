<?php

declare(strict_types=1);

namespace App\Telemetry;

use App\Models\TelemetryEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

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

    public const string SETTINGS_UPDATED = 'settings.updated';

    public const string LISTING_CREATED = 'listing.created';
    public const string LISTING_UPDATED = 'listing.updated';
    public const string LISTING_DELETED = 'listing.deleted';

    /**
     * Record a telemetry event. Auto-populates user, ip and user agent from
     * the current request/auth context; callers only need to pass the event
     * name (one of the constants above) plus an optional subject + context.
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
            'context' => empty($context) ? null : $context,
            'occurred_at' => now(),
        ]);
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
