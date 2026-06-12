<x-admin-layout title="Users" active="users">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300">Assign roles, pause access, or remove accounts.</p>
        </div>
        <p class="text-sm text-stone-500 dark:text-stone-400">{{ $users->count() }} {{ Str::plural('user', $users->count()) }}</p>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100" data-testid="flash-status">{{ session('status') }}</div>
    @endif
    @if ($errors->has('user'))
        <div class="mb-4 rounded-xl border border-rose-200 bg-rose-50 dark:bg-rose-950 dark:border-rose-800 p-3 text-sm text-rose-900 dark:text-rose-100" data-testid="flash-error">{{ $errors->first('user') }}</div>
    @endif

    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                <tr>
                    <th class="px-4 sm:px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium hidden md:table-cell">Email</th>
                    <th class="px-5 py-3 font-medium hidden lg:table-cell">Roles</th>
                    <th class="px-5 py-3 font-medium hidden sm:table-cell">Status</th>
                    <th class="px-4 sm:px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                @foreach ($users as $user)
                    <tr @class([
                        'odd:bg-stone-50/60 dark:odd:bg-stone-900 hover:bg-amber-50/60 dark:hover:bg-stone-800 transition-colors',
                        'opacity-60' => $user->isDeactivated(),
                    ]) data-testid="user-row-{{ $user->id }}">
                        <td class="px-4 sm:px-5 py-4 font-medium text-stone-900 dark:text-stone-100">
                            <div class="flex items-center gap-3">
                                <x-user-avatar :user="$user" size="sm" />
                                <div class="min-w-0">
                                    <p class="flex items-center gap-1.5">
                                        <span class="truncate">{{ $user->name }}</span>
                                        {{-- Mobile-only status dot — the Status column is
                                             hidden below sm; rose = deactivated. --}}
                                        @if ($user->isDeactivated())
                                            <span class="sm:hidden shrink-0 w-2 h-2 rounded-full bg-rose-500" title="Deactivated" aria-label="Deactivated"></span>
                                        @endif
                                    </p>
                                    {{-- Email + roles fold into the name cell while their
                                         columns are hidden on small screens. --}}
                                    <p class="md:hidden mt-0.5 text-xs font-normal text-stone-500 dark:text-stone-400 truncate">{{ $user->email }}</p>
                                    <p class="lg:hidden mt-0.5 text-[11px] font-normal text-stone-500 dark:text-stone-400 truncate">{{ $user->roles->pluck('name')->join(' · ') ?: 'no roles' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-5 py-4 text-stone-600 dark:text-stone-300 hidden md:table-cell">{{ $user->email }}</td>
                        <td class="px-5 py-4 hidden lg:table-cell">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($user->roles as $role)
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border',
                                        'bg-amber-100 text-amber-900 border-amber-200 dark:border-stone-800' => $role->name === \App\Roles::SUPERUSER,
                                        'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border-stone-200 dark:border-stone-800' => $role->name !== \App\Roles::SUPERUSER,
                                    ])>{{ $role->name }}</span>
                                @empty
                                    <span class="text-stone-400 italic">no roles</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4 hidden sm:table-cell">
                            @if ($user->isDeactivated())
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-rose-100 dark:bg-rose-950 text-rose-900 dark:text-rose-200 border border-rose-200 dark:border-rose-800"
                                      title="Deactivated {{ $user->deactivated_at->toFormattedDateString() }}"
                                      data-testid="status-deactivated-{{ $user->id }}">Deactivated</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">Active</span>
                            @endif
                        </td>
                        <td class="px-4 sm:px-5 py-4 text-right align-top sm:align-middle">
                            <div class="flex flex-col items-end gap-1.5 sm:inline-flex sm:flex-row sm:items-center sm:gap-3 sm:justify-end sm:flex-wrap">
                                @can(\App\Permissions::USERS_IMPERSONATE)
                                    @php($canImpersonate = $user->id !== auth()->id() && ! $user->isDeactivated() && (! $user->hasRole(\App\Roles::SUPERUSER) || auth()->user()->hasRole(\App\Roles::SUPERUSER)))
                                    @if ($canImpersonate)
                                        <form method="POST" action="{{ route('admin.impersonate.user', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 font-medium" data-testid="impersonate-button">Impersonate</button>
                                        </form>
                                    @endif
                                @endcan
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-stone-700 dark:text-stone-100 hover:underline font-medium">Edit</a>

                                @if ($user->id !== auth()->id())
                                    @if ($user->isDeactivated())
                                        <form method="POST" action="{{ route('admin.users.reactivate', $user) }}" class="inline">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-emerald-700 dark:text-emerald-400 hover:underline font-medium" data-testid="reactivate-user-{{ $user->id }}">Reactivate</button>
                                        </form>
                                    @else
                                        <x-confirm-form
                                            :action="route('admin.users.deactivate', $user)"
                                            method="PATCH"
                                            :title="'Deactivate '.$user->name.'?'"
                                            body="They are signed out on their next request and can no longer log in. Their data and content stay intact. Reactivate any time."
                                            confirm-label="Deactivate"
                                            class="inline">
                                            <button type="button" class="text-amber-700 dark:text-amber-400 hover:underline font-medium" data-testid="deactivate-user-{{ $user->id }}">Deactivate</button>
                                        </x-confirm-form>
                                    @endif

                                    <x-confirm-form
                                        :action="route('admin.users.destroy', $user)"
                                        method="DELETE"
                                        :title="'Permanently delete '.$user->name.'?'"
                                        body="Removes the account, their marketplace listings (with photos), owned Pragati Darpan schemes, and profile photo. Varieties, advisories and events they authored are kept with the author cleared. This cannot be undone — prefer Deactivate unless data removal is required."
                                        confirm-label="Delete permanently"
                                        class="inline">
                                        <button type="button" class="text-rose-700 dark:text-rose-400 hover:underline font-medium" data-testid="delete-user-{{ $user->id }}">Delete</button>
                                    </x-confirm-form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
