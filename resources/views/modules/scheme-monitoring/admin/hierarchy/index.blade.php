<x-admin-layout title="Pragati Darpan hierarchy" active="monitoring-hierarchy">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Pragati Darpan hierarchy</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Tag each <code>Samikshak</code>-role user with their designations. Reporting flows through the designation tree (set on <a href="{{ route('admin.monitoring.designations.index') }}" class="underline">Designations</a>): a viewer sees their own tasks and everything assigned to users holding descendant designations.</p>
    </header>

    @if ($candidates->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 dark:border-stone-800 bg-amber-50 dark:bg-stone-900 p-4 text-sm" data-testid="monitor-candidates">
            <p class="font-medium text-amber-900">Not yet enrolled</p>
            <p class="text-amber-800 mt-1">These users hold the <code>Samikshak</code> role but have no monitoring profile yet.</p>
            <ul class="mt-2 list-disc pl-5 text-amber-900">
                @foreach ($candidates as $u)
                    <li>{{ $u->name }} ({{ $u->email }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    {{-- Per-row update forms live OUTSIDE the table — putting a <form>
         directly inside a <tr> is invalid HTML and browsers reparent it
         away from the cells, leaving the controls form-orphaned. We bind
         the picker's hidden inputs and Save button via the HTML5 `form`
         attribute instead. --}}
    @foreach ($allMonitors as $monitor)
        <form id="hierarchy-form-{{ $monitor->id }}" method="POST" action="{{ route('admin.monitoring.hierarchy.update', $monitor) }}" class="hidden">
            @csrf
        </form>
    @endforeach

    {{-- Div-based responsive list instead of a <table>: the designation
         picker is too wide for four real columns on a phone, and the card
         wrapper's rounded corners would clip a wide table (no horizontal
         scrolling, per the house rule). lg+ renders the same four-column
         layout the table had; below lg each user becomes a labeled card. --}}
    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800" data-testid="hierarchy-table">
        @if ($enrolled->isEmpty() && $candidates->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No monitors yet. Assign the <code>Samikshak</code> role to a user from <a href="{{ route('admin.users.index') }}" class="underline">Users</a>.</p>
        @else
            {{-- Column headings (desktop only) --}}
            <div class="hidden lg:grid lg:grid-cols-[minmax(11rem,15rem)_1fr_minmax(9rem,13rem)_auto] gap-4 px-4 py-2 bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-sm font-medium rounded-t-2xl">
                <p>User</p>
                <p>Designations</p>
                <p>Reports to</p>
                <p class="text-right pr-1">&nbsp;</p>
            </div>

            <div class="divide-y divide-stone-100 dark:divide-stone-800 text-sm">
                @foreach ($allMonitors as $monitor)
                    @php($profile = $enrolled[$monitor->id] ?? null)
                    @php($effectiveParents = $effectiveParentsByUserId[$monitor->id] ?? collect())
                    @php($current = $profile?->user?->designations->pluck('id')->all() ?? [])
                    <div class="px-4 py-4 lg:py-3 odd:bg-white dark:odd:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900 lg:grid lg:grid-cols-[minmax(11rem,15rem)_1fr_minmax(9rem,13rem)_auto] lg:gap-4 lg:items-start space-y-3 lg:space-y-0"
                         data-testid="hierarchy-row-{{ $monitor->id }}">

                        <div class="min-w-0">
                            <p class="font-medium truncate">{{ $monitor->name }}</p>
                            <p class="text-xs text-stone-500 dark:text-stone-400 truncate">{{ $monitor->email }}</p>
                        </div>

                        <div class="min-w-0">
                            <p class="lg:hidden text-[11px] uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Designations</p>
                            <x-scheme-monitoring.designation-picker
                                name="designation_ids[]"
                                :options="$designations"
                                :selected="$current"
                                form="hierarchy-form-{{ $monitor->id }}"
                            />
                        </div>

                        <div class="min-w-0">
                            <p class="lg:hidden text-[11px] uppercase tracking-wider text-stone-500 dark:text-stone-400 mb-1">Reports to</p>
                            @if ($effectiveParents->isEmpty())
                                <span class="text-stone-400 text-xs">—</span>
                            @else
                                <div class="flex flex-wrap gap-1" data-testid="effective-parents-{{ $monitor->id }}">
                                    @foreach ($effectiveParents as $parent)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 text-xs" title="{{ $parent['via'] }}">
                                            {{ $parent['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="flex items-center gap-3 lg:justify-end text-xs pt-1 lg:pt-0 border-t border-stone-100 dark:border-stone-800 lg:border-0">
                            <button type="submit" form="hierarchy-form-{{ $monitor->id }}" class="inline-flex px-3 py-1.5 lg:py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800">Save</button>
                            @if ($profile)
                                <form method="POST" action="{{ route('admin.monitoring.hierarchy.destroy', $monitor) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-700 dark:text-rose-400 hover:underline">Remove</button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</x-admin-layout>
