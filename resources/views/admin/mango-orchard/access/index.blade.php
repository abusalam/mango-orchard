<x-admin-layout title="Mango Orchard access" active="mango-access">
    <header class="mb-6">
        <div class="flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Mango Orchard module access</h1>
                <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Grant the <code>mango-orchard-member</code> role so the user can self-apply for grower, curator, convener or advisor on their profile page. You can pre-assign any of those sub-roles in the same step.</p>
            </div>
            <p class="text-sm text-stone-500 dark:text-stone-400" data-testid="mango-member-count">{{ $memberCount }} {{ $memberCount === 1 ? 'member' : 'members' }}</p>
        </div>

        <form method="GET" class="mt-4 flex flex-wrap gap-3 items-end">
            <div>
                <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Search</label>
                
        <input type="text" name="q" value="{{ $search }}" placeholder="name or email"
                       class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm"
     data-testid="mango-access-search">
            </div>
            <div>
                <label class="block text-xs text-stone-600 dark:text-stone-300 mb-1">Filter</label>
                
        <select name="only" class="rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm"
    >
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="all" @selected($only === 'all')>All users</option>
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="members" @selected($only === 'members')>In the module</option>
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="non-members" @selected($only === 'non-members')>Not yet added</option>
                </select>
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">Apply</button>
            <a href="{{ route('admin.mango-orchard.access.index') }}" class="text-xs text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline">Reset</a>
        </form>
    </header>

    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden" data-testid="mango-access-table">
        @if ($users->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No users match your filter.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                    <tr>
                        <th class="px-4 py-2 font-medium">User</th>
                        <th class="px-4 py-2 font-medium hidden md:table-cell">Sub-roles to grant on add</th>
                        <th class="px-4 py-2 font-medium">Status</th>
                        <th class="px-4 py-2 font-medium text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach ($users as $user)
                        @php($isMember = $user->hasRole(\App\Roles::MANGO_ORCHARD_MEMBER))
                        <tr class="odd:bg-white dark:odd:bg-stone-950 dark:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="mango-access-row-{{ $user->id }}">
                            <form method="POST" action="{{ route('admin.mango-orchard.access.grant', $user) }}">
                                @csrf
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-900 dark:text-stone-100">{{ $user->name }}</p>
                                    <p class="text-xs text-stone-500 dark:text-stone-400">{{ $user->email }}</p>
                                    @php($currentSub = $user->roles->pluck('name')->intersect($subRoles)->all())
                                    @if (! empty($currentSub))
                                        <p class="text-[10px] text-stone-500 dark:text-stone-400 mt-1">Holds: {{ implode(', ', $currentSub) }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-xs hidden md:table-cell">
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($subRoles as $sr)
                                            <label class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full border border-stone-200 dark:border-stone-800 bg-stone-50 dark:bg-stone-900 cursor-pointer has-[:checked]:bg-amber-50 dark:bg-stone-900 has-[:checked]:border-amber-300">
                                                <input type="checkbox" name="sub_roles[]" value="{{ $sr }}" class="rounded text-amber-500 focus:ring-amber-400">
                                                <span>{{ $sr }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    @if ($isMember)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-emerald-100 text-emerald-900 text-xs font-medium" data-testid="mango-status-member">In module</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-xs" data-testid="mango-status-non-member">Not added</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    @if (! $isMember)
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs font-medium"
                                                data-testid="mango-grant-button">Add to module</button>
                                    @else
                                        <button type="submit"
                                                class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 hover:bg-stone-200 dark:hover:bg-stone-600 text-xs"
                                                data-testid="mango-grant-button">Update sub-roles</button>
                                    @endif
                            </form>
                            @if ($isMember)
                                <form method="POST" action="{{ route('admin.mango-orchard.access.revoke', $user) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                            class="inline-flex items-center px-3 py-1.5 rounded-full bg-rose-50 text-rose-900 border border-rose-200 hover:bg-rose-100 text-xs font-medium"
                                            data-testid="mango-revoke-button">Remove</button>
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

    <div class="mt-8 text-sm text-stone-500 dark:text-stone-400">
        <p><strong>Note:</strong> Once added, the user can self-apply for grower / curator / convener / advisor from their profile page. Admins can also assign sub-roles directly via <a href="{{ route('admin.users.index') }}" class="underline hover:text-stone-900 dark:text-stone-100">Users</a>.</p>
    </div>
</x-admin-layout>
