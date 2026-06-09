<x-admin-layout title="Activity" active="telemetry">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Activity</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300">Recent events recorded by the app's telemetry hooks.</p>
        </div>
        <p class="text-sm text-stone-500 dark:text-stone-400" data-testid="telemetry-count">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}</p>
    </header>

    <form method="GET" action="{{ route('admin.telemetry.index') }}" class="mb-4 flex items-center gap-3 flex-wrap">
        <label for="event" class="text-sm text-stone-700 dark:text-stone-300">Filter by event:</label>
        
        <select name="event" id="event" onchange="this.form.submit()"
                class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm focus:border-orange-400 focus:ring-orange-400 max-w-full"
    >
            <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="">All events</option>
            @foreach ($eventOptions as $name)
                <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $name }}" @selected($filterEvent === $name)>{{ $name }}</option>
            @endforeach
        </select>
        @if ($filterEvent !== '')
            <a href="{{ route('admin.telemetry.index') }}" class="text-xs text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100">Clear</a>
        @endif
    </form>

    {{-- ── Mobile: stacked cards (below sm = under 640px) ─────────────── --}}
    {{-- A table can't hold 3+ columns under ~400px without cramming. At
         phone widths we render each event as a vertically-stacked card
         instead. Above sm: switches to the partial-stacked table below. --}}
    <div class="sm:hidden bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
        @forelse ($events as $event)
            <article class="px-4 py-3 border-b border-stone-100 dark:border-stone-800 last:border-b-0" data-testid="telemetry-row-mobile">
                <div class="flex flex-wrap items-baseline gap-x-2 gap-y-1">
                    <span class="font-mono text-xs text-stone-900 dark:text-stone-100 break-all">{{ $event->event }}</span>
                    <span class="text-[11px] text-stone-400" title="{{ $event->occurred_at }}">{{ $event->occurred_at->diffForHumans() }}</span>
                </div>
                <div class="mt-1 text-sm text-stone-700 dark:text-stone-300">
                    @if ($event->user)
                        <strong class="font-medium">{{ $event->user->name }}</strong>
                        <span class="block text-[11px] text-stone-400 truncate">{{ $event->user->email }}</span>
                    @else
                        <em class="text-stone-400">guest</em>
                    @endif
                    <x-impersonated-tag :event="$event" />
                </div>
                <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-stone-500 dark:text-stone-400" data-testid="telemetry-trace">
                    @if ($event->subject_type)
                        <span class="font-mono text-stone-600 dark:text-stone-300">
                            <span class="text-stone-400">subject</span> {{ class_basename($event->subject_type) }}#{{ $event->subject_id }}
                        </span>
                    @endif
                    @if ($event->ip_address)
                        <span class="font-mono text-stone-600 dark:text-stone-300" title="IP address">
                            <span class="text-stone-400">ip</span> {{ $event->ip_address }}
                        </span>
                    @endif
                    @if ($event->session_id)
                        <span class="font-mono text-stone-600 dark:text-stone-300" title="Session: {{ $event->session_id }}">
                            <span class="text-stone-400">sess</span> {{ Str::limit($event->session_id, 8, '…') }}
                        </span>
                    @endif
                    @if ($event->user_agent)
                        <span class="text-stone-500 dark:text-stone-400 max-w-full truncate" title="{{ $event->user_agent }}">
                            <span class="text-stone-400">ua</span> {{ $event->user_agent }}
                        </span>
                    @endif
                </div>
                @if ($event->context)
                    <div class="mt-1 text-[11px] text-stone-500 dark:text-stone-400 min-w-0">
                        <span class="text-stone-400">context</span>
                        <code class="text-stone-600 dark:text-stone-300 break-all" title="{{ json_encode($event->context) }}">{{ Str::limit(json_encode($event->context), 120) }}</code>
                    </div>
                @endif
            </article>
        @empty
            <p class="px-5 py-10 text-center text-stone-500 dark:text-stone-400" data-testid="telemetry-empty-mobile">
                No events recorded yet.
            </p>
        @endforelse
    </div>

    {{-- ── sm: and up: partial-stacked table ──────────────────────────── --}}
    {{-- Five columns at desktop, with Subject column collapsing into the
         Details cell below md:. trace + context stacked in Details. --}}
    <div class="hidden sm:block bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
        <table class="w-full text-sm table-fixed">
            <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                <tr>
                    <th class="px-4 py-3 font-medium w-24">When</th>
                    <th class="px-4 py-3 font-medium w-48">Event</th>
                    <th class="px-4 py-3 font-medium w-44">Actor</th>
                    <th class="px-4 py-3 font-medium w-28 hidden md:table-cell">Subject</th>
                    <th class="px-4 py-3 font-medium">Details</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                @forelse ($events as $event)
                    <tr class="align-top odd:bg-stone-50/60 dark:odd:bg-stone-900 hover:bg-amber-50/60 dark:hover:bg-stone-800 transition-colors" data-testid="telemetry-row">
                        <td class="px-4 py-3 text-xs text-stone-600 dark:text-stone-300" title="{{ $event->occurred_at }}">
                            {{ $event->occurred_at->diffForHumans() }}
                        </td>
                        <td class="px-4 py-3 font-mono text-xs text-stone-800 dark:text-stone-200 break-all">{{ $event->event }}</td>
                        <td class="px-4 py-3 text-stone-700 dark:text-stone-300 min-w-0">
                            <div class="text-sm break-words">
                                @if ($event->user)
                                    <strong class="font-medium">{{ $event->user->name }}</strong>
                                    <span class="block text-[11px] text-stone-400 truncate">{{ $event->user->email }}</span>
                                @else
                                    <em class="text-stone-400">guest</em>
                                @endif
                            </div>
                            <x-impersonated-tag :event="$event" />
                        </td>
                        <td class="px-4 py-3 font-mono text-[11px] text-stone-700 dark:text-stone-300 hidden md:table-cell break-all">
                            @if ($event->subject_type)
                                {{ class_basename($event->subject_type) }}#{{ $event->subject_id }}
                            @else
                                <span class="text-stone-300">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-[11px] text-stone-600 dark:text-stone-300 min-w-0" data-testid="telemetry-trace">
                            <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                                @if ($event->ip_address)
                                    <span class="font-mono text-stone-700 dark:text-stone-300" title="IP address">
                                        <span class="text-stone-400">ip</span> {{ $event->ip_address }}
                                    </span>
                                @endif
                                @if ($event->session_id)
                                    <span class="font-mono text-stone-600 dark:text-stone-300" title="Session: {{ $event->session_id }}">
                                        <span class="text-stone-400">sess</span> {{ Str::limit($event->session_id, 8, '…') }}
                                    </span>
                                @endif
                                @if ($event->user_agent)
                                    <span class="text-stone-500 dark:text-stone-400 max-w-[16rem] truncate" title="{{ $event->user_agent }}">
                                        <span class="text-stone-400">ua</span> {{ $event->user_agent }}
                                    </span>
                                @endif
                                @if ($event->subject_type)
                                    {{-- Inline subject only when the Subject column is hidden (below md). --}}
                                    <span class="font-mono text-stone-600 dark:text-stone-300 md:hidden">
                                        <span class="text-stone-400">subject</span> {{ class_basename($event->subject_type) }}#{{ $event->subject_id }}
                                    </span>
                                @endif
                                @if (! $event->ip_address && ! $event->session_id && ! $event->user_agent && ! $event->subject_type)
                                    <span class="text-stone-300 italic">—</span>
                                @endif
                            </div>

                            @if ($event->context)
                                <div class="mt-1.5 min-w-0">
                                    <span class="text-stone-400">context</span>
                                    <code class="text-stone-600 dark:text-stone-300 break-all" title="{{ json_encode($event->context) }}">{{ Str::limit(json_encode($event->context), 140) }}</code>
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-stone-500 dark:text-stone-400" data-testid="telemetry-empty">
                            No events recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
</x-admin-layout>
