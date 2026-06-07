<x-admin-layout title="Pragati Darpan hierarchy" active="monitoring-hierarchy">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Pragati Darpan hierarchy</h1>
        <p class="mt-1 text-stone-600 text-sm">Tag each <code>Samikshak</code>-role user with their designations. Reporting flows through the designation tree (set on <a href="{{ route('admin.monitoring.designations.index') }}" class="underline">Designations</a>): a viewer sees their own tasks and everything assigned to users holding descendant designations.</p>
    </header>

    @if ($candidates->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm" data-testid="monitor-candidates">
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

    <div class="bg-white rounded-2xl border border-stone-200" data-testid="hierarchy-table">
        @if ($enrolled->isEmpty() && $candidates->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No monitors yet. Assign the <code>Samikshak</code> role to a user from <a href="{{ route('admin.users.index') }}" class="underline">Users</a>.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-left">
                    <tr><th class="px-4 py-2">User</th><th class="px-4 py-2">Designations</th><th class="px-4 py-2">Reports to</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($allMonitors as $monitor)
                        @php($profile = $enrolled[$monitor->id] ?? null)
                        @php($effectiveParents = $effectiveParentsByUserId[$monitor->id] ?? collect())
                        @php($current = $profile?->user?->designations->pluck('id')->all() ?? [])
                        <tr class="odd:bg-white even:bg-stone-50/50" data-testid="hierarchy-row-{{ $monitor->id }}">
                            <td class="px-4 py-3 align-top">
                                <p class="font-medium">{{ $monitor->name }}</p>
                                <p class="text-xs text-stone-500">{{ $monitor->email }}</p>
                            </td>
                            <td class="px-4 py-3 align-top">
                                <x-scheme-monitoring.designation-picker
                                    name="designation_ids[]"
                                    :options="$designations"
                                    :selected="$current"
                                    form="hierarchy-form-{{ $monitor->id }}"
                                />
                            </td>
                            <td class="px-4 py-3 align-top">
                                @if ($effectiveParents->isEmpty())
                                    <span class="text-stone-400 text-xs">—</span>
                                @else
                                    <div class="flex flex-wrap gap-1" data-testid="effective-parents-{{ $monitor->id }}">
                                        @foreach ($effectiveParents as $parent)
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs" title="{{ $parent['via'] }}">
                                                {{ $parent['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3 align-top text-right text-xs whitespace-nowrap space-x-3">
                                <button type="submit" form="hierarchy-form-{{ $monitor->id }}" class="inline-flex px-3 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800">Save</button>
                                @if ($profile)
                                    <form method="POST" action="{{ route('admin.monitoring.hierarchy.destroy', $monitor) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-700 hover:underline">Remove</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin-layout>
