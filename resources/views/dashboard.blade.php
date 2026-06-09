<x-site-layout :title="'Dashboard — Aamar Malda'">
    <div class="py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <header class="flex items-center justify-between">
                <div>
                    <h2 class="font-semibold text-xl text-stone-900 dark:text-stone-100 leading-tight">{{ __('Dashboard') }}</h2>
                    <p class="text-sm text-stone-500 dark:text-stone-400">Welcome back, {{ $user->name }}.</p>
                </div>
                <p class="text-xs text-stone-400">Member since {{ $personal['member_since']->toFormattedDateString() }}</p>
            </header>

            @if (session('status'))
                <div class="bg-emerald-50 border border-emerald-200 text-emerald-900 rounded-2xl p-4 text-sm" data-testid="flash-status">
                    {{ session('status') }}
                </div>
            @endif

            {{-- ╭─ Section 1 — "Needs your attention" ─────────────────────── --}}
            @if (! empty($attention))
                <section data-testid="dashboard-attention" class="space-y-3">
                    <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">Needs your attention</h3>

                    @isset($attention['pending_role_applications'])
                        <a href="{{ $attention['pending_role_applications']['href'] }}"
                           class="block bg-amber-50 dark:bg-stone-900 border border-amber-200 dark:border-stone-800 rounded-2xl p-4 hover:border-amber-400 transition-colors"
                           data-testid="attention-pending-role-applications">
                            <p class="text-sm text-amber-900">
                                <strong data-testid="attention-pending-role-applications-count">{{ $attention['pending_role_applications']['count'] }}</strong>
                                {{ Str::plural('role application', $attention['pending_role_applications']['count']) }} waiting for review →
                            </p>
                        </a>
                    @endisset

                    @isset($attention['failed_logins_24h'])
                        <a href="{{ $attention['failed_logins_24h']['href'] }}"
                           class="block bg-rose-50 border border-rose-200 rounded-2xl p-4 hover:border-rose-400 transition-colors">
                            <p class="text-sm text-rose-900">
                                <strong>{{ $attention['failed_logins_24h']['count'] }}</strong>
                                failed logins in the last 24 hours — investigate →
                            </p>
                        </a>
                    @endisset

                    @isset($attention['dev_flags_on'])
                        <a href="{{ $attention['dev_flags_on']['href'] }}"
                           class="block bg-rose-50 border border-rose-200 rounded-2xl p-4 hover:border-rose-400 transition-colors">
                            <p class="text-sm text-rose-900">
                                Dev-only setting{{ count($attention['dev_flags_on']['flags']) === 1 ? '' : 's' }} on:
                                <strong>{{ implode(', ', $attention['dev_flags_on']['flags']) }}</strong>.
                                Disable before going to production →
                            </p>
                        </a>
                    @endisset

                    @isset($attention['my_application_decision'])
                        @php
                            $decision = $attention['my_application_decision']['application'];
                        @endphp
                        <a href="{{ $attention['my_application_decision']['href'] }}"
                           class="block rounded-2xl p-4 transition-colors {{ $decision->status === \App\Models\RoleApplication::STATUS_APPROVED
                                ? 'bg-emerald-50 border border-emerald-200 hover:border-emerald-400'
                                : 'bg-stone-50 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-stone-400' }}">
                            <p class="text-sm {{ $decision->status === \App\Models\RoleApplication::STATUS_APPROVED ? 'text-emerald-900' : 'text-stone-700 dark:text-stone-300' }}">
                                Your application for the
                                <strong>{{ $decision->role?->name }}</strong> role was
                                <strong>{{ $decision->status === \App\Models\RoleApplication::STATUS_APPROVED ? 'approved' : 'rejected' }}</strong>
                                {{ $decision->reviewed_at?->diffForHumans() }}.
                                @if ($decision->decision_note)
                                    Reviewer note: <em>{{ $decision->decision_note }}</em>
                                @endif
                            </p>
                        </a>
                    @endisset
                </section>
            @endif

            {{-- ╭─ Section 1b — Active advisories (everyone sees, grower-relevant) ── --}}
            @if ($advisories->isNotEmpty())
                <section data-testid="dashboard-advisories" class="space-y-3">
                    <div class="flex items-end justify-between">
                        <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">Active advisories</h3>
                        <a href="{{ route('advisories.index') }}" class="text-xs text-orange-700 hover:text-orange-900 font-medium">All advisories →</a>
                    </div>
                    <div class="space-y-3">
                        @foreach ($advisories as $advisory)
                            <x-advisory-card :advisory="$advisory" :compact="true" />
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- ╭─ Section 2 — Your activity ───────────────────────────────── --}}
            <section data-testid="dashboard-personal" class="space-y-3">
                <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">Your activity</h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">

                    {{-- Role + member-since card --}}
                    <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                        <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Your role</p>
                        <div class="mt-2 flex flex-wrap gap-1.5">
                            @forelse ($personal['roles'] as $roleName)
                                <span @class([
                                    'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border',
                                    'bg-amber-100 text-amber-900 border-amber-200 dark:border-stone-800' => $roleName === \App\Roles::SUPERUSER,
                                    'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border-stone-200 dark:border-stone-800' => $roleName !== \App\Roles::SUPERUSER,
                                ])>{{ $roleName }}</span>
                            @empty
                                <span class="text-sm text-stone-400 italic">no roles yet</span>
                            @endforelse
                        </div>
                        <p class="mt-4 text-xs text-stone-500 dark:text-stone-400">
                            <a href="{{ route('profile.edit') }}" class="text-orange-700 hover:text-orange-900 font-medium">
                                @if ($personal['pending_application'])
                                    1 pending request → review
                                @elseif (count($personal['roles']) === 0 || count($personal['roles']) === 1)
                                    Request another role →
                                @else
                                    Manage profile →
                                @endif
                            </a>
                        </p>
                    </div>

                    {{-- Listings card (growers + superusers) --}}
                    @isset($personal['listings'])
                        <a href="{{ route('my.listings.index') }}"
                           class="block bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5 hover:border-stone-400 transition-colors">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Your listings</p>
                            <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100">{{ $personal['listings']['total'] }}</p>
                            <div class="mt-3 flex flex-wrap gap-2 text-[11px]">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 border border-emerald-200">{{ $personal['listings']['published'] }} published</span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-800">{{ $personal['listings']['draft'] }} draft</span>
                                @if ($personal['listings']['sold_out'] > 0)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-rose-100 text-rose-900 border border-rose-200">{{ $personal['listings']['sold_out'] }} sold out</span>
                                @endif
                            </div>
                        </a>
                    @endisset

                    {{-- Onboarding nudge for users with no role and no pending app --}}
                    @if (count($personal['roles']) === 0 && ! $personal['pending_application'] && ! isset($personal['listings']))
                        <a href="{{ route('profile.edit') }}"
                           class="block bg-amber-50 dark:bg-stone-900 border border-amber-200 dark:border-stone-800 rounded-2xl p-5 hover:border-amber-400 transition-colors">
                            <p class="text-sm font-medium text-amber-900">Want to do more?</p>
                            <p class="mt-1 text-xs text-amber-900/80">Apply for a role to unlock listing your harvest or running events.</p>
                        </a>
                    @endif
                </div>
            </section>

            {{-- ╭─ Section 3 — Orchard-wide stats (admin-ish) ──────────────── --}}
            @if ($orchard)
                <section data-testid="dashboard-orchard" class="space-y-3">
                    <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">At a glance</h3>

                    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">

                        @isset($orchard['users'])
                            <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Users</p>
                                <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100">{{ number_format($orchard['users']['total']) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">+{{ $orchard['users']['last_week'] }} this week</p>
                            </div>
                        @endisset

                        @isset($orchard['listings'])
                            <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Listings live</p>
                                <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100">{{ number_format($orchard['listings']['published']) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">+{{ $orchard['listings']['last_week'] }} this week</p>
                            </div>
                        @endisset

                        @isset($orchard['varieties'])
                            <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Varieties</p>
                                <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100">{{ number_format($orchard['varieties']['total']) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">in the catalogue</p>
                            </div>
                        @endisset

                        @isset($orchard['events'])
                            <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Upcoming events</p>
                                <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100" data-testid="orchard-events-upcoming">{{ number_format($orchard['events']['upcoming']) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">scheduled ahead</p>
                            </div>
                        @endisset

                        @isset($orchard['telemetry_24h'])
                            <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Events / 24h</p>
                                <p class="mt-2 text-3xl font-semibold text-stone-900 dark:text-stone-100">{{ number_format($orchard['telemetry_24h']) }}</p>
                                <p class="mt-1 text-[11px] text-stone-500 dark:text-stone-400">app-wide telemetry</p>
                            </div>
                        @endisset
                    </div>
                </section>
            @endif

            {{-- ╭─ Section 4 — Telemetry insights (gated on TELEMETRY_VIEW) ─ --}}
            @if ($telemetryInsights !== null)
                <section data-testid="dashboard-telemetry-insights" class="space-y-3">
                    <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">Telemetry insights</h3>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">

                        {{-- Daily volume sparkline (14d) --}}
                        <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5">
                            <div class="flex items-baseline justify-between">
                                <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Events / day · last 14 days</p>
                                <p class="text-xs text-stone-400" data-testid="telemetry-daily-total">{{ number_format($telemetryInsights['daily_volume_total']) }} total</p>
                            </div>

                            @php
                                $days = $telemetryInsights['daily_volume'];
                                $max = max(1, $telemetryInsights['daily_volume_max']);
                                $width = 320; $height = 60; $pad = 4;
                                $stride = (count($days) > 1) ? ($width / (count($days) - 1)) : 0;
                                $points = [];
                                foreach ($days as $i => $point) {
                                    $x = round($i * $stride, 1);
                                    $y = round($pad + (1 - $point['count'] / $max) * ($height - 2 * $pad), 1);
                                    $points[] = "$x,$y";
                                }
                                $polyline = implode(' ', $points);
                                // Area polygon closes by dropping to the bottom edge then back to start.
                                $area = $polyline." {$width},{$height} 0,{$height}";
                            @endphp

                            <svg viewBox="0 0 {{ $width }} {{ $height }}" class="mt-3 w-full h-16 overflow-visible" preserveAspectRatio="none" aria-label="Daily telemetry volume over the last 14 days" data-testid="telemetry-sparkline">
                                <polygon points="{{ $area }}" fill="#fef3c7" />
                                <polyline points="{{ $polyline }}" fill="none" stroke="#f97316" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" vector-effect="non-scaling-stroke" />
                                @foreach ($days as $i => $point)
                                    @php
                                        $cx = round($i * $stride, 1);
                                        $cy = round($pad + (1 - $point['count'] / $max) * ($height - 2 * $pad), 1);
                                    @endphp
                                    <circle cx="{{ $cx }}" cy="{{ $cy }}" r="2" fill="#f97316">
                                        <title>{{ $point['label'] }}: {{ $point['count'] }}</title>
                                    </circle>
                                @endforeach
                            </svg>

                            <div class="mt-2 flex justify-between text-[10px] text-stone-400">
                                <span>{{ $days[0]['label'] }}</span>
                                <span>{{ end($days)['label'] }}</span>
                            </div>
                        </div>

                        {{-- Auth health snapshot (7d) --}}
                        <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5" data-testid="telemetry-auth-health">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Auth health · last 7 days</p>

                            @php
                                $health = $telemetryInsights['auth_health'];
                                $totalAuth = $health['succeeded'] + $health['failed'];
                                $successPct = $totalAuth > 0 ? round($health['succeeded'] / $totalAuth * 100) : 0;
                            @endphp

                            <div class="mt-3 flex items-baseline gap-4">
                                <div>
                                    <p class="text-3xl font-semibold text-emerald-700" data-testid="telemetry-auth-success-pct">{{ $successPct }}%</p>
                                    <p class="text-[11px] text-stone-500 dark:text-stone-400">login success rate</p>
                                </div>
                                <div class="text-right ml-auto space-y-0.5">
                                    <p class="text-xs text-stone-700 dark:text-stone-300"><strong class="text-emerald-700">{{ number_format($health['succeeded']) }}</strong> succeeded</p>
                                    <p class="text-xs text-stone-700 dark:text-stone-300"><strong class="text-rose-700 dark:text-rose-400">{{ number_format($health['failed']) }}</strong> failed</p>
                                    @if ($health['captcha_failed'] > 0)
                                        <p class="text-xs text-stone-700 dark:text-stone-300"><strong class="text-amber-700">{{ number_format($health['captcha_failed']) }}</strong> captcha failed</p>
                                    @endif
                                </div>
                            </div>

                            @if ($totalAuth > 0)
                                <div class="mt-4 h-2 rounded-full overflow-hidden bg-stone-100 dark:bg-stone-800 flex" aria-hidden="true">
                                    <div class="h-full bg-emerald-500" style="width: {{ $successPct }}%"></div>
                                    <div class="h-full bg-rose-500" style="width: {{ 100 - $successPct }}%"></div>
                                </div>
                            @else
                                <p class="mt-4 text-xs text-stone-400 italic">No login activity in the window yet.</p>
                            @endif

                            <p class="mt-4 text-xs text-stone-500 dark:text-stone-400"><strong data-testid="telemetry-active-users-7d">{{ number_format($telemetryInsights['active_users_7d']) }}</strong> distinct users active in the last 7 days</p>
                        </div>

                        {{-- Top events bar chart (7d) --}}
                        <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5 lg:col-span-2">
                            <p class="text-xs uppercase tracking-wide text-stone-500 dark:text-stone-400">Top events · last 7 days</p>

                            @if (empty($telemetryInsights['top_events']))
                                <p class="mt-4 text-sm text-stone-400 italic">No telemetry recorded yet in this window.</p>
                            @else
                                <div class="mt-4 space-y-2" data-testid="telemetry-top-events">
                                    @foreach ($telemetryInsights['top_events'] as $row)
                                        @php
                                            $pct = $telemetryInsights['top_events_max'] > 0
                                                ? round($row['count'] / $telemetryInsights['top_events_max'] * 100)
                                                : 0;
                                        @endphp
                                        <div class="flex items-center gap-3 text-xs">
                                            <a href="{{ route('admin.telemetry.index', ['event' => $row['event']]) }}" class="font-mono text-stone-700 dark:text-stone-300 w-44 sm:w-56 truncate hover:text-stone-900 dark:text-stone-100" title="{{ $row['event'] }}">{{ $row['event'] }}</a>
                                            <div class="flex-1 h-2 bg-stone-100 dark:bg-stone-800 rounded-full overflow-hidden">
                                                <div class="h-full bg-gradient-to-r from-amber-400 to-orange-500" style="width: {{ $pct }}%"></div>
                                            </div>
                                            <span class="font-medium text-stone-700 dark:text-stone-300 w-10 text-right" data-testid="telemetry-top-events-count">{{ number_format($row['count']) }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>
            @endif

            {{-- ╭─ Section 5 — Recent activity feed (telemetry viewers) ────── --}}
            @if ($recentActivity !== null)
                <section data-testid="dashboard-activity">
                    <div class="flex items-end justify-between mb-3">
                        <h3 class="text-xs font-semibold tracking-wide uppercase text-stone-500 dark:text-stone-400">Latest activity</h3>
                        <a href="{{ route('admin.telemetry.index') }}" class="text-xs text-orange-700 hover:text-orange-900 font-medium">View full feed →</a>
                    </div>

                    <div class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl overflow-hidden">
                        @if ($recentActivity->isEmpty())
                            <p class="p-6 text-sm text-stone-500 dark:text-stone-400 italic">No activity yet.</p>
                        @else
                            <ul class="divide-y divide-stone-100 dark:divide-stone-800 text-sm">
                                @foreach ($recentActivity as $event)
                                    <li class="px-5 py-3 flex items-center gap-3" data-testid="dashboard-activity-row">
                                        <span class="font-mono text-xs text-stone-700 dark:text-stone-300">{{ $event->event }}</span>
                                        <span class="text-stone-400 text-xs">·</span>
                                        <span class="text-stone-600 dark:text-stone-300 text-xs">
                                            @if ($event->user)
                                                {{ $event->user->name }}
                                            @else
                                                <em class="text-stone-400">guest</em>
                                            @endif
                                        </span>
                                        <x-impersonated-tag :event="$event" />
                                        <span class="ml-auto text-xs text-stone-400" title="{{ $event->occurred_at }}">{{ $event->occurred_at->diffForHumans() }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </section>
            @endif
        </div>
    </div>
</x-site-layout>
