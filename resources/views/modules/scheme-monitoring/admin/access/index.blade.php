<x-admin-layout title="Pragati Darpan access" active="monitoring-access">
    <header class="mb-6">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Pragati Darpan module access</h1>
                <p class="mt-1 text-stone-600 text-sm">Grant the <code>Samikshak</code> role and create a profile in one click. Users without a profile here will never see the Pragati Darpan dashboard, even if they hold the role from elsewhere.</p>
            </div>
            <p class="text-sm text-stone-500" data-testid="monitoring-member-count">{{ $memberCount }} {{ $memberCount === 1 ? 'member' : 'members' }}</p>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-stone-600 mb-1">Search</label>
                <input type="text" name="q" value="{{ $search }}" placeholder="name or email"
                       class="rounded-lg border-stone-300 text-sm" data-testid="access-search">
            </div>
            <div>
                <label class="block text-xs text-stone-600 mb-1">Filter</label>
                <select name="only" class="rounded-lg border-stone-300 text-sm">
                    <option value="all" @selected($only === 'all')>All users</option>
                    <option value="members" @selected($only === 'members')>In the module</option>
                    <option value="non-members" @selected($only === 'non-members')>Not yet added</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">Apply</button>
            <a href="{{ route('admin.monitoring.access.index') }}" class="text-xs text-stone-500 hover:text-stone-900 underline">Reset</a>
        </form>
    </header>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden" data-testid="access-table">
        @if ($users->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No users match your filter.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">User</th>
                        <th class="px-4 py-2 font-medium hidden sm:table-cell">Other roles</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 font-medium text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($users as $user)
                        @php($isMember = $user->hasRole(\App\Roles::MONITOR))
                        @php($hasProfile = $user->monitoringProfile !== null)
                        <tr class="odd:bg-white even:bg-stone-50/50" data-testid="access-row-{{ $user->id }}">
                            <td class="px-4 py-3">
                                <p class="font-medium text-stone-900">{{ $user->name }}</p>
                                <p class="text-xs text-stone-500">{{ $user->email }}</p>
                            </td>
                            <td class="px-4 py-3 text-xs text-stone-600 hidden sm:table-cell">
                                @forelse ($user->roles->where('name', '!=', \App\Roles::MONITOR)->where('name', '!=', \App\Roles::MONITOR_ADMIN) as $role)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 mr-1 mb-1">{{ $role->name }}</span>
                                @empty
                                    <span class="text-stone-400">—</span>
                                @endforelse
                            </td>
                            <td class="px-4 py-3">
                                @if ($isMember && $hasProfile)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 text-xs font-medium" data-testid="status-member">In module</span>
                                @elseif ($isMember && ! $hasProfile)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-amber-100 text-amber-900 text-xs font-medium" data-testid="status-partial" title="Has the monitor role but no profile — re-grant to create one.">Profile missing</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 text-stone-600 text-xs" data-testid="status-non-member">Not added</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                @if ($isMember)
                                    <form method="POST" action="{{ route('admin.monitoring.access.revoke', $user) }}" class="inline">
                                        @csrf @method('DELETE')
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-full bg-rose-50 text-rose-900 border border-rose-200 hover:bg-rose-100 text-xs font-medium"
                                                data-testid="revoke-button">Remove</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('admin.monitoring.access.grant', $user) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs font-medium"
                                                data-testid="grant-button">Add to module</button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $users->links() }}</div>

    <div class="mt-8 text-sm text-stone-500">
        <p><strong>Next step after granting access:</strong> place the user in the org chart and tag their designations from <a href="{{ route('admin.monitoring.hierarchy.index') }}" class="underline hover:text-stone-900">Hierarchy</a>.</p>
    </div>
</x-admin-layout>
