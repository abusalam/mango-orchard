<x-site-layout :title="'Pragati Darpan dashboard — '.config('app.name')">
    <section class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-8">
        <header class="mb-6 flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">Pragati Darpan dashboard</h1>
                <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Tasks visible to you {{ $canManage ? '(managing all)' : '(your subtree)' }}.</p>
            </div>
            <div class="flex gap-2 text-sm">
                <a href="{{ route('monitoring.schemes.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 transition-colors">New scheme</a>
                <a href="{{ route('monitoring.tasks.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors font-medium" data-testid="new-task-link">New task</a>
                <a href="{{ route('monitoring.schemes.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-full border border-stone-300 hover:bg-stone-100 dark:hover:bg-stone-700">All schemes</a>
            </div>
        </header>

        {{-- Stats ---------------------------------------- --}}
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6" data-testid="monitoring-stats">
            @foreach ([
                ['k' => 'open', 'label' => 'Open', 'css' => 'bg-stone-50 dark:bg-stone-900 text-stone-900 dark:text-stone-100 border-stone-200 dark:border-stone-800'],
                ['k' => 'overdue', 'label' => 'Overdue', 'css' => 'bg-rose-50 text-rose-900 border-rose-200'],
                ['k' => 'due_this_week', 'label' => 'Due this week', 'css' => 'bg-amber-50 dark:bg-stone-900 text-amber-900 border-amber-200 dark:border-stone-800'],
                ['k' => 'completed', 'label' => 'Completed', 'css' => 'bg-emerald-50 text-emerald-900 border-emerald-200'],
            ] as $card)
                <div class="rounded-2xl border p-4 {{ $card['css'] }}">
                    <p class="text-xs font-medium uppercase tracking-wider opacity-70">{{ $card['label'] }}</p>
                    <p class="mt-1 text-2xl font-semibold" data-testid="stat-{{ $card['k'] }}">{{ $stats[$card['k']] }}</p>
                </div>
            @endforeach
        </div>

        {{-- Two-column layout: sidebar (sticky on md+) + main area. Flex,
             not grid, because some form-as-grid-container variants didn't
             render the columns side by side; flex with explicit widths is
             foolproof. Each form (sidebar + top filter row) auto-submits
             and round-trips the other one's state via hidden mirrors so
             nothing is lost when one filter changes. --}}
        <div class="flex flex-col md:flex-row gap-4 items-start">

            {{-- Sidebar form ---------------------------------------- --}}
            @php
                // Active selections across all four groups — shown on the
                // mobile Filters toggle so a collapsed panel still tells
                // the user something is narrowing the list.
                $activeFilterCount = count($filters['windows'])
                    + count($filters['statuses'])
                    + count($filters['designations'])
                    + count($filters['assignees']);
            @endphp
            <aside class="w-full md:w-60 md:shrink-0 md:sticky md:top-4"
                   x-data="{ filtersOpen: window.matchMedia('(min-width: 768px)').matches }"
                   data-testid="assignee-sidebar">

                {{-- Mobile-only disclosure toggle. On md+ the panel is always
                     visible (md:!block below) and this button is hidden. --}}
                <button type="button"
                        @click="filtersOpen = !filtersOpen"
                        :aria-expanded="filtersOpen.toString()"
                        aria-controls="sidebar-filters"
                        class="md:hidden w-full flex items-center justify-between gap-2 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl px-4 py-3 text-sm font-medium text-stone-800 dark:text-stone-200"
                        data-testid="mobile-filters-toggle">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M2.5 4.5h15M5 10h10M8 15.5h4" stroke-linecap="round"/>
                        </svg>
                        Filters
                        @if ($activeFilterCount > 0)
                            <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-amber-500 text-stone-900 text-[11px] font-semibold" data-testid="mobile-filters-count">{{ $activeFilterCount }}</span>
                        @endif
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="filtersOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 8l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                {{-- x-show drives mobile collapse; md:!block overrides the
                     inline display:none so desktop is unaffected even if the
                     panel was collapsed before a resize. The form stays in
                     the DOM either way, so auto-submit + hidden mirrors keep
                     working exactly as before. --}}
                <form method="GET" action="{{ route('monitoring.dashboard') }}" id="sidebar-filters"
                      x-show="filtersOpen"
                      class="mt-2 md:mt-0 md:!block bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-4 md:max-h-[calc(100vh-2rem)] md:overflow-y-auto">
                    {{-- Round-trip everything we don't own here — including
                         each group's mode (include / exclude). --}}
                    <x-scheme-monitoring.preserve-filters
                        :filters="$filters"
                        :except="[
                            'assignees', 'designations', 'statuses', 'windows',
                            'assignees_mode', 'designations_mode', 'statuses_mode', 'windows_mode',
                        ]"
                    />

                    {{-- By window ---------------------------------------- --}}
                    <section data-testid="sidebar-group-window">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold {{ $filters['windows_mode'] === 'exclude' ? 'text-rose-700 dark:text-rose-400' : 'text-stone-900 dark:text-stone-100' }}">
                                By window{{ $filters['windows_mode'] === 'exclude' ? ' (NOT)' : '' }}
                            </h2>
                            <div class="flex items-center gap-2 text-[11px]">
                                <label class="inline-flex items-center gap-1 cursor-pointer text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:text-stone-300" title="Exclude the checked items instead of restricting to them">
                                    <input type="checkbox" name="windows_mode" value="exclude"
                                           @checked($filters['windows_mode'] === 'exclude')
                                           onchange="this.form.submit()"
                                           class="rounded text-rose-500 focus:ring-rose-400 w-3 h-3"
                                           data-testid="windows-mode-toggle">
                                    Exclude
                                </label>
                                @if (! empty($filters['windows']))
                                    <a href="{{ route('monitoring.dashboard', collect($filters)->except(['windows', 'windows_mode'])->filter()->all()) }}"
                                       class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline"
                                       data-testid="window-clear">Clear</a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 space-y-0.5" data-testid="window-list">
                            @foreach ([
                                'overdue' => 'Overdue',
                                'today' => 'Due today',
                                '3day' => 'Next 3 days',
                                '7day' => 'Next 7 days',
                                'upcoming' => 'Next 14 days',
                                'open' => 'All open',
                            ] as $value => $label)
                                <label
                                    class="flex items-center gap-2 py-2 md:py-1 px-2 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-900 cursor-pointer text-sm"
                                    title="{{ $label }}"
                                >
                                    <input
                                        type="checkbox"
                                        name="windows[]"
                                        value="{{ $value }}"
                                        @checked(in_array($value, $filters['windows'], true))
                                        onchange="this.form.submit()"
                                        class="rounded text-amber-500 focus:ring-amber-400"
                                        data-testid="window-checkbox-{{ $value }}"
                                    >
                                    <span class="truncate text-stone-800 dark:text-stone-200">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <div class="my-3 border-t border-stone-100 dark:border-stone-800"></div>

                    {{-- By status ---------------------------------------- --}}
                    <section data-testid="sidebar-group-status">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold {{ $filters['statuses_mode'] === 'exclude' ? 'text-rose-700 dark:text-rose-400' : 'text-stone-900 dark:text-stone-100' }}">
                                By status{{ $filters['statuses_mode'] === 'exclude' ? ' (NOT)' : '' }}
                            </h2>
                            <div class="flex items-center gap-2 text-[11px]">
                                <label class="inline-flex items-center gap-1 cursor-pointer text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:text-stone-300" title="Exclude the checked items instead of restricting to them">
                                    <input type="checkbox" name="statuses_mode" value="exclude"
                                           @checked($filters['statuses_mode'] === 'exclude')
                                           onchange="this.form.submit()"
                                           class="rounded text-rose-500 focus:ring-rose-400 w-3 h-3"
                                           data-testid="statuses-mode-toggle">
                                    Exclude
                                </label>
                                @if (! empty($filters['statuses']))
                                    <a href="{{ route('monitoring.dashboard', collect($filters)->except(['statuses', 'statuses_mode'])->filter()->all()) }}"
                                       class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline"
                                       data-testid="status-clear">Clear</a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 space-y-0.5" data-testid="status-list">
                            @foreach (\App\Modules\SchemeMonitoring\Models\Task::STATUSES as $value => $label)
                                <label
                                    class="flex items-center gap-2 py-2 md:py-1 px-2 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-900 cursor-pointer text-sm"
                                    title="{{ $label }}"
                                >
                                    <input
                                        type="checkbox"
                                        name="statuses[]"
                                        value="{{ $value }}"
                                        @checked(in_array($value, $filters['statuses'], true))
                                        onchange="this.form.submit()"
                                        class="rounded text-amber-500 focus:ring-amber-400"
                                        data-testid="status-checkbox-{{ $value }}"
                                    >
                                    <span class="truncate text-stone-800 dark:text-stone-200">{{ $label }}</span>
                                </label>
                            @endforeach
                        </div>
                    </section>

                    <div class="my-3 border-t border-stone-100 dark:border-stone-800"></div>

                    {{-- By designation ---------------------------------------- --}}
                    <section data-testid="sidebar-group-designation">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold {{ $filters['designations_mode'] === 'exclude' ? 'text-rose-700 dark:text-rose-400' : 'text-stone-900 dark:text-stone-100' }}">
                                By designation{{ $filters['designations_mode'] === 'exclude' ? ' (NOT)' : '' }}
                            </h2>
                            <div class="flex items-center gap-2 text-[11px]">
                                <label class="inline-flex items-center gap-1 cursor-pointer text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:text-stone-300" title="Exclude the checked items instead of restricting to them">
                                    <input type="checkbox" name="designations_mode" value="exclude"
                                           @checked($filters['designations_mode'] === 'exclude')
                                           onchange="this.form.submit()"
                                           class="rounded text-rose-500 focus:ring-rose-400 w-3 h-3"
                                           data-testid="designations-mode-toggle">
                                    Exclude
                                </label>
                                @if (! empty($filters['designations']))
                                    <a href="{{ route('monitoring.dashboard', collect($filters)->except(['designations', 'designations_mode'])->filter()->all()) }}"
                                       class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline"
                                       data-testid="designation-clear">Clear</a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 max-h-44 overflow-y-auto pr-1 -mr-1 space-y-0.5" data-testid="designation-list">
                            @forelse ($designations as $designation)
                                <label
                                    class="flex items-center justify-between gap-2 py-2 md:py-1 px-2 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-900 cursor-pointer text-sm"
                                    title="{{ $designation->name }}"
                                >
                                    <span class="flex items-center gap-2 min-w-0">
                                        <input
                                            type="checkbox"
                                            name="designations[]"
                                            value="{{ $designation->id }}"
                                            @checked(in_array($designation->id, $filters['designations'], true))
                                            onchange="this.form.submit()"
                                            class="rounded text-amber-500 focus:ring-amber-400"
                                            data-testid="designation-checkbox-{{ $designation->id }}"
                                        >
                                        <span class="truncate text-stone-800 dark:text-stone-200">{{ $designation->name }}</span>
                                    </span>
                                    <span class="shrink-0 text-[10px] text-stone-500 dark:text-stone-400 tabular-nums">{{ $taskCountsByDesignation[$designation->id] ?? 0 }}</span>
                                </label>
                            @empty
                                <p class="text-xs text-stone-500 dark:text-stone-400 py-2">No designations assigned in your subtree.</p>
                            @endforelse
                        </div>
                    </section>

                    <div class="my-3 border-t border-stone-100 dark:border-stone-800"></div>

                    {{-- By user ---------------------------------------- --}}
                    <section data-testid="sidebar-group-user">
                        <div class="flex items-center justify-between gap-2">
                            <h2 class="text-sm font-semibold {{ $filters['assignees_mode'] === 'exclude' ? 'text-rose-700 dark:text-rose-400' : 'text-stone-900 dark:text-stone-100' }}">
                                By user{{ $filters['assignees_mode'] === 'exclude' ? ' (NOT)' : '' }}
                            </h2>
                            <div class="flex items-center gap-2 text-[11px]">
                                <label class="inline-flex items-center gap-1 cursor-pointer text-stone-500 dark:text-stone-400 hover:text-stone-700 dark:text-stone-300" title="Exclude the checked items instead of restricting to them">
                                    <input type="checkbox" name="assignees_mode" value="exclude"
                                           @checked($filters['assignees_mode'] === 'exclude')
                                           onchange="this.form.submit()"
                                           class="rounded text-rose-500 focus:ring-rose-400 w-3 h-3"
                                           data-testid="assignees-mode-toggle">
                                    Exclude
                                </label>
                                @if (! empty($filters['assignees']))
                                    <a href="{{ route('monitoring.dashboard', collect($filters)->except(['assignees', 'assignees_mode'])->filter()->all()) }}"
                                       class="text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline"
                                       data-testid="assignee-clear">Clear</a>
                                @endif
                            </div>
                        </div>
                        <div class="mt-2 max-h-72 overflow-y-auto pr-1 -mr-1 space-y-0.5" data-testid="assignee-list">
                            @forelse ($assignableUsers as $user)
                                <label
                                    class="flex items-center justify-between gap-2 py-2 md:py-1 px-2 rounded-lg hover:bg-stone-50 dark:hover:bg-stone-900 cursor-pointer text-sm"
                                    title="{{ $user->name }} · {{ $user->email }}"
                                >
                                    <span class="flex items-center gap-2 min-w-0">
                                        <input
                                            type="checkbox"
                                            name="assignees[]"
                                            value="{{ $user->id }}"
                                            @checked(in_array($user->id, $filters['assignees'], true))
                                            onchange="this.form.submit()"
                                            class="rounded text-amber-500 focus:ring-amber-400"
                                            data-testid="assignee-checkbox-{{ $user->id }}"
                                        >
                                        <span class="truncate text-stone-800 dark:text-stone-200">{{ $user->name }}</span>
                                    </span>
                                    <span class="shrink-0 text-[10px] text-stone-500 dark:text-stone-400 tabular-nums">{{ $taskCountsByAssignee[$user->id] ?? 0 }}</span>
                                </label>
                            @empty
                                <p class="text-xs text-stone-500 dark:text-stone-400 py-2">No one in your subtree yet.</p>
                            @endforelse
                        </div>
                    </section>
                </form>
            </aside>

            {{-- Main column ---------------------------------------- --}}
            <main class="flex-1 min-w-0 w-full">
                {{-- Top filter form. Mirrors the sidebar's state via hidden
                     inputs so submitting one form doesn't drop the other. --}}
                <form method="GET" action="{{ route('monitoring.dashboard') }}" id="top-filters" class="mb-4 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-4 flex flex-wrap gap-3 items-end text-sm" data-testid="monitoring-filters">
                    <x-scheme-monitoring.preserve-filters :filters="$filters" :except="['scheme', 'sort', 'direction']" />

                    <div>
                        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Scheme</label>
                        
        <select name="scheme" onchange="this.form.submit()" class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm"
    >
                            <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="">Any</option>
                            @foreach ($schemes as $s)
                                <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $s->id }}" @selected((int) $filters['scheme'] === $s->id)>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Sort by</label>
                        
        <select name="sort" onchange="this.form.submit()" class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm"
    >
                            @foreach (['deadline' => 'Deadline', 'priority' => 'Priority', 'created' => 'Created'] as $v => $label)
                                <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $v }}" @selected($filters['sort'] === $v)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Order</label>
                        
        <select name="direction" onchange="this.form.submit()" class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm"
    >
                            <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="asc" @selected($filters['direction'] === 'asc')>Ascending</option>
                            <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="desc" @selected($filters['direction'] === 'desc')>Descending</option>
                        </select>
                    </div>
                    <a href="{{ route('monitoring.dashboard') }}" class="text-xs text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline ml-auto">Reset all</a>
                </form>

                {{-- Task cards ---------------------------------------- --}}
                <div data-testid="monitoring-tasks">
                    @if ($tasks->isEmpty())
                        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 px-6 py-12 text-center text-stone-500 dark:text-stone-400">
                            No tasks match your filters.
                        </div>
                    @else
                        @php
                            // Status → card background tint. Pending stays
                            // neutral; in-progress, completed and cancelled
                            // each tint the whole card so status is readable
                            // at a glance with no dedicated column.
                            $statusBg = [
                                \App\Modules\SchemeMonitoring\Models\Task::STATUS_PENDING => 'bg-white dark:bg-stone-950',
                                \App\Modules\SchemeMonitoring\Models\Task::STATUS_IN_PROGRESS => 'bg-amber-50 dark:bg-stone-900',
                                \App\Modules\SchemeMonitoring\Models\Task::STATUS_COMPLETED => 'bg-emerald-50',
                                \App\Modules\SchemeMonitoring\Models\Task::STATUS_CANCELLED => 'bg-stone-100 dark:bg-stone-800',
                            ];
                        @endphp
                        <div class="space-y-3">
                            @foreach ($tasks as $task)
                                <article
                                    class="{{ $statusBg[$task->status] ?? 'bg-white dark:bg-stone-950' }} border border-stone-200 dark:border-stone-800 rounded-2xl p-4 {{ $task->isOverdue() ? 'border-l-4 border-l-rose-400' : '' }}"
                                    data-testid="task-row-{{ $task->id }}"
                                    data-status="{{ $task->status }}"
                                    title="Status: {{ \App\Modules\SchemeMonitoring\Models\Task::STATUSES[$task->status] }}"
                                >
                                    <div class="flex items-start justify-between gap-4">
                                        <div class="flex items-start gap-3 min-w-0 flex-1">
                                            @if ($task->scheme)
                                                <span
                                                    class="inline-flex shrink-0 items-center justify-center min-w-[2.5rem] px-2 py-1 rounded-md bg-stone-900 text-amber-50 text-[10px] font-bold tracking-wider mt-0.5"
                                                    title="{{ $task->scheme->name }}"
                                                    data-testid="scheme-chip-{{ $task->id }}"
                                                >{{ $task->scheme->displayAbbreviation() }}</span>
                                            @endif
                                            <div class="min-w-0">
                                                <p class="font-medium text-stone-900 dark:text-stone-100 break-words leading-snug">{{ $task->title }}</p>
                                                <p class="text-[11px] text-stone-500 dark:text-stone-400 truncate mt-0.5">
                                                    @if ($task->scheme){{ $task->scheme->name }} · @endif
                                                    <span data-testid="task-assignee-inline-{{ $task->id }}">{{ $task->assignee?->name ?? 'Unassigned' }}</span>
                                                </p>
                                                @php
                                                    // Window anchor matches the deadline bar: task.created_at.
                                                    // The scheme's start_date is the umbrella project timeline
                                                    // (often a full financial year) — using it here drowns the
                                                    // signal in hundreds of days for any mid-year task.
                                                    $chipStartDay = $task->created_at->copy()->startOfDay();
                                                    // Inclusive day count — every day from start through
                                                    // deadline is one in the window. Same-day window is "1d",
                                                    // Mon→Tue is "2d", etc.
                                                    $durationDays = (int) $chipStartDay->diffInDays(
                                                        $task->deadline->copy()->startOfDay()
                                                    ) + 1;
                                                @endphp
                                                <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                                    {{-- Priority chip — solid colour-coded for emphasis,
                                                         but skipped for the default `normal` priority
                                                         since it's the unremarkable baseline. --}}
                                                    @if ($task->priority !== \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_NORMAL)
                                                        <span
                                                            @class([
                                                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-bold uppercase tracking-wider ring-1 ring-inset',
                                                                'bg-rose-600 text-white ring-rose-700' => $task->priority === \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_URGENT,
                                                                'bg-orange-500 text-white ring-orange-600' => $task->priority === \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_HIGH,
                                                                'bg-stone-400 text-white ring-stone-500' => $task->priority === \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_LOW,
                                                            ])
                                                            title="Priority: {{ \App\Modules\SchemeMonitoring\Models\Task::PRIORITIES[$task->priority] }}"
                                                            data-testid="task-priority-{{ $task->id }}"
                                                            data-priority="{{ $task->priority }}"
                                                        >
                                                            @if ($task->priority === \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_URGENT)
                                                                {{-- exclamation in a triangle --}}
                                                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                                                                    <line x1="12" y1="9" x2="12" y2="13"/>
                                                                    <line x1="12" y1="17" x2="12.01" y2="17"/>
                                                                </svg>
                                                            @elseif ($task->priority === \App\Modules\SchemeMonitoring\Models\Task::PRIORITY_HIGH)
                                                                {{-- arrow up --}}
                                                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <line x1="12" y1="19" x2="12" y2="5"/>
                                                                    <polyline points="5 12 12 5 19 12"/>
                                                                </svg>
                                                            @else
                                                                {{-- arrow down for low --}}
                                                                <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <line x1="12" y1="5" x2="12" y2="19"/>
                                                                    <polyline points="19 12 12 19 5 12"/>
                                                                </svg>
                                                            @endif
                                                            {{ \App\Modules\SchemeMonitoring\Models\Task::PRIORITIES[$task->priority] }}
                                                        </span>
                                                    @endif

                                                    {{-- Task duration (window from start to deadline) --}}
                                                    <span
                                                        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300"
                                                        title="From {{ $chipStartDay->format('d M Y') }} to {{ $task->deadline->format('d M Y') }}"
                                                        data-testid="task-duration-{{ $task->id }}"
                                                    >
                                                        <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                            <rect x="3" y="4" width="18" height="18" rx="2"/>
                                                            <line x1="3" y1="10" x2="21" y2="10"/>
                                                            <line x1="8" y1="2" x2="8" y2="6"/>
                                                            <line x1="16" y1="2" x2="16" y2="6"/>
                                                        </svg>
                                                        {{ $durationDays }}d window
                                                    </span>

                                                    {{-- Attachment chips — click to reveal a popover list of
                                                         the actual files. Two chips: one for this task's own
                                                         attachments (sky) and one for the parent scheme's
                                                         attachments (violet). Each chip is hidden when its
                                                         attachment collection is empty so cards stay clean. --}}
                                                    @if ($task->attachments->isNotEmpty())
                                                        <div x-data="{ open: false }" class="relative inline-block z-10">
                                                            <button
                                                                type="button"
                                                                @click="open = !open"
                                                                @click.away="open = false"
                                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-sky-100 text-sky-800 hover:bg-sky-200 transition-colors"
                                                                title="Task attachments"
                                                                data-testid="task-attachments-chip-{{ $task->id }}"
                                                            >
                                                                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.34a2 2 0 0 1-2.83-2.83L15.07 6"/>
                                                                </svg>
                                                                {{ $task->attachments->count() }}
                                                            </button>
                                                            <div
                                                                x-show="open" x-cloak x-transition.opacity
                                                                class="absolute top-full left-0 mt-1 z-30 w-64 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-xl shadow-lg p-2"
                                                                data-testid="task-attachments-popover-{{ $task->id }}"
                                                            >
                                                                <p class="text-[10px] text-stone-500 dark:text-stone-400 uppercase tracking-wider px-2 mb-1">Task files</p>
                                                                <ul class="space-y-0.5">
                                                                    @foreach ($task->attachments as $a)
                                                                        <li>
                                                                            <a
                                                                                href="{{ $a->url() }}"
                                                                                target="_blank"
                                                                                rel="noopener"
                                                                                class="block px-2 py-1 text-xs text-stone-800 dark:text-stone-200 hover:bg-stone-50 dark:bg-stone-900 rounded truncate"
                                                                                title="{{ $a->original_name }} · {{ $a->humanSize() }}"
                                                                            >{{ $a->original_name }}</a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    @endif

                                                    @if ($task->scheme && $task->scheme->attachments->isNotEmpty())
                                                        <div x-data="{ open: false }" class="relative inline-block z-10">
                                                            <button
                                                                type="button"
                                                                @click="open = !open"
                                                                @click.away="open = false"
                                                                class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-violet-100 text-violet-800 hover:bg-violet-200 transition-colors"
                                                                title="Scheme attachments — {{ $task->scheme->name }}"
                                                                data-testid="scheme-attachments-chip-{{ $task->id }}"
                                                            >
                                                                <svg class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                                    <path d="M22 19a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h5l2 3h9a2 2 0 0 1 2 2z"/>
                                                                </svg>
                                                                {{ $task->scheme->attachments->count() }}
                                                            </button>
                                                            <div
                                                                x-show="open" x-cloak x-transition.opacity
                                                                class="absolute top-full left-0 mt-1 z-30 w-64 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-xl shadow-lg p-2"
                                                                data-testid="scheme-attachments-popover-{{ $task->id }}"
                                                            >
                                                                <p class="text-[10px] text-stone-500 dark:text-stone-400 uppercase tracking-wider px-2 mb-1">Scheme files · {{ $task->scheme->displayAbbreviation() }}</p>
                                                                <ul class="space-y-0.5">
                                                                    @foreach ($task->scheme->attachments as $a)
                                                                        <li>
                                                                            <a
                                                                                href="{{ $a->url() }}"
                                                                                target="_blank"
                                                                                rel="noopener"
                                                                                class="block px-2 py-1 text-xs text-stone-800 dark:text-stone-200 hover:bg-stone-50 dark:bg-stone-900 rounded truncate"
                                                                                title="{{ $a->original_name }} · {{ $a->humanSize() }}"
                                                                            >{{ $a->original_name }}</a>
                                                                        </li>
                                                                    @endforeach
                                                                </ul>
                                                            </div>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <a href="{{ route('monitoring.tasks.edit', $task) }}" class="shrink-0 text-xs text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 underline">Edit</a>
                                    </div>

                                    {{-- Full-width progress bar below the card content. --}}
                                    <div class="mt-3">
                                        <x-scheme-monitoring.deadline-bar :task="$task" />
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="mt-4">{{ $tasks->links() }}</div>
            </main>
        </div>
    </section>
</x-site-layout>
