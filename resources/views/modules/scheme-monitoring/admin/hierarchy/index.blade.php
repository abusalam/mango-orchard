<x-admin-layout title="Monitoring hierarchy" active="monitoring">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">Monitoring hierarchy</h1>
        <p class="mt-1 text-stone-600 text-sm">Place each <code>monitor</code>-role user under a parent and tag them with their designations. Visibility on the dashboard follows this tree: a viewer sees their own tasks and everything assigned to their descendants.</p>
    </header>

    @if ($candidates->isNotEmpty())
        <div class="mb-6 rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm" data-testid="monitor-candidates">
            <p class="font-medium text-amber-900">Not yet enrolled</p>
            <p class="text-amber-800 mt-1">These users hold the <code>monitor</code> role but have no monitoring profile yet.</p>
            <ul class="mt-2 list-disc pl-5 text-amber-900">
                @foreach ($candidates as $u)
                    <li>{{ $u->name }} ({{ $u->email }})</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden" data-testid="hierarchy-table">
        @if ($enrolled->isEmpty() && $candidates->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No monitors yet. Assign the <code>monitor</code> role to a user from <a href="{{ route('admin.users.index') }}" class="underline">Users</a>.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-left">
                    <tr><th class="px-4 py-2">User</th><th class="px-4 py-2">Designations</th><th class="px-4 py-2">Parent</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($allMonitors as $monitor)
                        @php($profile = $enrolled[$monitor->id] ?? null)
                        <tr class="odd:bg-white even:bg-stone-50/50" data-testid="hierarchy-row-{{ $monitor->id }}">
                            <form method="POST" action="{{ route('admin.monitoring.hierarchy.update', $monitor) }}">
                                @csrf
                                <td class="px-4 py-3">
                                    <p class="font-medium">{{ $monitor->name }}</p>
                                    <p class="text-xs text-stone-500">{{ $monitor->email }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <select name="designation_ids[]" multiple size="3" class="rounded-lg border-stone-300 text-xs min-w-[12rem]">
                                        @php($current = $profile?->user?->designations->pluck('id')->all() ?? [])
                                        @foreach ($designations as $d)
                                            <option value="{{ $d->id }}" @selected(in_array($d->id, $current, true))>{{ $d->name }}</option>
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3">
                                    <select name="parent_user_id" class="rounded-lg border-stone-300 text-xs">
                                        <option value="">— top of hierarchy —</option>
                                        @foreach ($allMonitors as $other)
                                            @if ($other->id !== $monitor->id)
                                                <option value="{{ $other->id }}" @selected($profile?->parent_user_id === $other->id)>{{ $other->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </td>
                                <td class="px-4 py-3 text-right text-xs whitespace-nowrap">
                                    <button type="submit" class="inline-flex px-3 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800">Save</button>
                                </td>
                            </form>
                            @if ($profile)
                                <td class="px-4 py-3 text-right text-xs">
                                    <form method="POST" action="{{ route('admin.monitoring.hierarchy.destroy', $monitor) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-rose-700 hover:underline">Remove</button>
                                    </form>
                                </td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</x-admin-layout>
