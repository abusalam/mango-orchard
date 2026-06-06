<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Modules\MangoOrchard\Models\Advisory;
use App\Modules\MangoOrchard\Models\Event;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\RoleApplication;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Permissions;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'verified'])];
    }

    public function show(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'user' => $user,
            'attention' => $this->attentionItems($user),
            'personal' => $this->personalStats($user),
            'orchard' => $this->orchardStats($user),
            'telemetryInsights' => $this->telemetryInsights($user),
            'advisories' => $this->activeAdvisoriesFor($user),
            'recentActivity' => $user->can(Permissions::TELEMETRY_VIEW)
                ? TelemetryEvent::with('user')->latest('occurred_at')->limit(8)->get()
                : null,
        ]);
    }

    /**
     * Active advisories most relevant to the viewer:
     *   - Growers see advisories matching their listings' varieties first,
     *     then general (no-variety-pivot) advisories;
     *   - Everyone else sees all active advisories ordered by severity.
     * Top 5 only — the public /advisories page has the full list.
     */
    private function activeAdvisoriesFor(User $user): \Illuminate\Support\Collection
    {
        $varietyIds = $user->can(Permissions::LISTINGS_MANAGE)
            ? $user->listings()->pluck('mango_variety_id')->unique()->all()
            : [];

        return Advisory::query()
            ->with(['issuer', 'varieties'])
            ->active()
            ->when($varietyIds !== [], function ($q) use ($varietyIds) {
                $q->where(function ($w) use ($varietyIds) {
                    // Matches the grower's varieties OR is a general advisory
                    // (no variety pivot rows → applies to everything).
                    $w->whereHas('varieties', fn ($v) => $v->whereIn('mango_varieties.id', $varietyIds))
                      ->orWhereDoesntHave('varieties');
                });
            })
            ->orderByRaw("CASE severity WHEN 'urgent' THEN 3 WHEN 'warning' THEN 2 ELSE 1 END DESC")
            ->latest('issued_at')
            ->limit(5)
            ->get();
    }

    /**
     * Items that warrant a red/amber callout at the top of the dashboard.
     * Each is keyed so the view can render exactly what's present.
     */
    private function attentionItems(User $user): array
    {
        $items = [];

        if ($user->can(Permissions::USERS_MANAGE)) {
            $pending = RoleApplication::pending()->count();
            if ($pending > 0) {
                $items['pending_role_applications'] = [
                    'count' => $pending,
                    'href' => route('admin.role-applications.index'),
                ];
            }
        }

        if ($user->can(Permissions::TELEMETRY_VIEW)) {
            $failedLogins24h = TelemetryEvent::query()
                ->where('event', Telemetry::AUTH_LOGIN_FAILED)
                ->where('occurred_at', '>=', now()->subDay())
                ->count();
            if ($failedLogins24h >= 5) {
                $items['failed_logins_24h'] = [
                    'count' => $failedLogins24h,
                    'href' => route('admin.telemetry.index', ['event' => Telemetry::AUTH_LOGIN_FAILED]),
                ];
            }
        }

        if ($user->can(Permissions::SETTINGS_MANAGE)) {
            $settings = app(Settings::class);
            $devFlagsOn = collect([
                'Captcha autosolve' => $settings->captchaAutosolve(),
                'Form autofill' => $settings->formAutofill(),
            ])->filter()->keys()->all();
            if ($devFlagsOn !== []) {
                $items['dev_flags_on'] = [
                    'flags' => $devFlagsOn,
                    'href' => route('admin.settings.edit'),
                ];
            }
        }

        // The user's own most-recently-decided role application — surface it
        // once so they see whether they were approved or rejected.
        $latestOwnDecision = $user->roleApplications()
            ->with('role')
            ->whereIn('status', [RoleApplication::STATUS_APPROVED, RoleApplication::STATUS_REJECTED])
            ->where('reviewed_at', '>=', now()->subDays(14))
            ->latest('reviewed_at')
            ->first();
        if ($latestOwnDecision !== null) {
            $items['my_application_decision'] = [
                'application' => $latestOwnDecision,
                'href' => route('profile.edit'),
            ];
        }

        return $items;
    }

    /**
     * What the signed-in user has personally got going on. Shown to every
     * dashboard visitor, with cards tailored to their role.
     */
    private function personalStats(User $user): array
    {
        $stats = [
            'member_since' => $user->created_at,
            'roles' => $user->roles->pluck('name')->all(),
            'pending_application' => $user->roleApplications()
                ->with('role')
                ->where('status', RoleApplication::STATUS_PENDING)
                ->latest('created_at')
                ->first(),
        ];

        if ($user->can(Permissions::LISTINGS_MANAGE)) {
            $byStatus = $user->listings()
                ->selectRaw('status, count(*) as n')
                ->groupBy('status')
                ->pluck('n', 'status');

            $stats['listings'] = [
                'total' => (int) $byStatus->sum(),
                'published' => (int) ($byStatus[Listing::STATUS_PUBLISHED] ?? 0),
                'draft' => (int) ($byStatus[Listing::STATUS_DRAFT] ?? 0),
                'sold_out' => (int) ($byStatus[Listing::STATUS_SOLD_OUT] ?? 0),
            ];
        }

        return $stats;
    }

    /**
     * High-level "what's happening in the orchard" counters. Each tile is
     * gated on the *specific* permission that controls that admin section —
     * a grower (LISTINGS_MANAGE only) won't see global Users / Telemetry
     * counters they have no business looking at.
     */
    private function orchardStats(User $user): ?array
    {
        $stats = [];

        if ($user->can(Permissions::USERS_MANAGE)) {
            $stats['users'] = [
                'total' => User::count(),
                'last_week' => User::where('created_at', '>=', now()->subDays(7))->count(),
            ];
            // Marketplace-wide listing counts are an admin-only signal.
            // Growers see their own listings in the "personal" section.
            $stats['listings'] = [
                'published' => Listing::visible()->count(),
                'last_week' => Listing::where('created_at', '>=', now()->subDays(7))->count(),
            ];
        }

        if ($user->can(Permissions::VARIETIES_MANAGE)) {
            $stats['varieties'] = [
                'total' => MangoVariety::count(),
            ];
        }

        if ($user->can(Permissions::EVENTS_MANAGE)) {
            $stats['events'] = [
                'upcoming' => Event::query()
                    ->where('status', Event::STATUS_PUBLISHED)
                    ->where('start_at', '>=', now())
                    ->count(),
            ];
        }

        if ($user->can(Permissions::TELEMETRY_VIEW)) {
            $stats['telemetry_24h'] = TelemetryEvent::query()
                ->where('occurred_at', '>=', now()->subDay())
                ->count();
        }

        return $stats === [] ? null : $stats;
    }

    /**
     * Telemetry-derived charts: daily volume sparkline, top events bar
     * chart, auth health snapshot, distinct active users. Gated entirely
     * on TELEMETRY_VIEW — anyone who can't view the telemetry feed has no
     * business seeing these aggregates.
     */
    private function telemetryInsights(User $user): ?array
    {
        if (! $user->can(Permissions::TELEMETRY_VIEW)) {
            return null;
        }

        // Daily volume — last 14 days. We aggregate at the DB and then fill
        // any zero-count days in PHP so the sparkline has a stable x-axis.
        $since = now()->subDays(13)->startOfDay();
        $countsByDay = TelemetryEvent::query()
            ->where('occurred_at', '>=', $since)
            ->selectRaw('DATE(occurred_at) as day, COUNT(*) as n')
            ->groupBy('day')
            ->pluck('n', 'day');

        $dailyVolume = [];
        for ($i = 13; $i >= 0; $i--) {
            $day = now()->subDays($i)->toDateString();
            $dailyVolume[] = [
                'day' => $day,
                'label' => now()->subDays($i)->format('M j'),
                'count' => (int) ($countsByDay[$day] ?? 0),
            ];
        }

        // Top events in the last 7 days. Limit to a reasonable display set.
        $weekAgo = now()->subDays(7);
        $topEvents = TelemetryEvent::query()
            ->where('occurred_at', '>=', $weekAgo)
            ->selectRaw('event, COUNT(*) as n')
            ->groupBy('event')
            ->orderByDesc('n')
            ->limit(8)
            ->get()
            ->map(fn ($row) => ['event' => $row->event, 'count' => (int) $row->n])
            ->all();

        // Auth health snapshot — succeeded vs failed logins + captcha fails
        // across the last 7 days. Useful for spotting brute-force attempts.
        $authBuckets = TelemetryEvent::query()
            ->whereIn('event', [
                Telemetry::AUTH_LOGIN_SUCCEEDED,
                Telemetry::AUTH_LOGIN_FAILED,
                Telemetry::AUTH_CAPTCHA_FAILED,
            ])
            ->where('occurred_at', '>=', $weekAgo)
            ->selectRaw('event, COUNT(*) as n')
            ->groupBy('event')
            ->pluck('n', 'event');

        $authHealth = [
            'succeeded' => (int) ($authBuckets[Telemetry::AUTH_LOGIN_SUCCEEDED] ?? 0),
            'failed' => (int) ($authBuckets[Telemetry::AUTH_LOGIN_FAILED] ?? 0),
            'captcha_failed' => (int) ($authBuckets[Telemetry::AUTH_CAPTCHA_FAILED] ?? 0),
        ];

        // Distinct active users (anyone whose user_id appears in telemetry)
        // in the last 7 days. A rough but useful "engagement" gauge.
        $activeUsers7d = (int) TelemetryEvent::query()
            ->whereNotNull('user_id')
            ->where('occurred_at', '>=', $weekAgo)
            ->distinct('user_id')
            ->count('user_id');

        return [
            'daily_volume' => $dailyVolume,
            'daily_volume_total' => array_sum(array_column($dailyVolume, 'count')),
            'daily_volume_max' => max(array_column($dailyVolume, 'count') ?: [0]),
            'top_events' => $topEvents,
            'top_events_max' => empty($topEvents) ? 0 : max(array_column($topEvents, 'count')),
            'auth_health' => $authHealth,
            'active_users_7d' => $activeUsers7d,
        ];
    }
}
