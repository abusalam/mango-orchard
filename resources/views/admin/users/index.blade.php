<x-admin-layout title="Users" active="users">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300">Assign roles to control what each user can do.</p>
        </div>
        <p class="text-sm text-stone-500 dark:text-stone-400">{{ $users->count() }} {{ Str::plural('user', $users->count()) }}</p>
    </header>

    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Roles</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                @foreach ($users as $user)
                    <tr class="odd:bg-stone-50/60 dark:odd:bg-stone-900 hover:bg-amber-50/60 dark:hover:bg-stone-800 transition-colors">
                        <td class="px-5 py-4 font-medium text-stone-900 dark:text-stone-100">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-stone-600 dark:text-stone-300">{{ $user->email }}</td>
                        <td class="px-5 py-4">
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
                        <td class="px-5 py-4 text-right">
                            <div class="inline-flex items-center gap-3 justify-end">
                                @can(\App\Permissions::USERS_IMPERSONATE)
                                    @php($canImpersonate = $user->id !== auth()->id() && (! $user->hasRole(\App\Roles::SUPERUSER) || auth()->user()->hasRole(\App\Roles::SUPERUSER)))
                                    @if ($canImpersonate)
                                        <form method="POST" action="{{ route('admin.impersonate.user', $user) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 font-medium" data-testid="impersonate-button">Impersonate</button>
                                        </form>
                                    @endif
                                @endcan
                                <a href="{{ route('admin.users.edit', $user) }}" class="text-stone-700 dark:text-stone-100 hover:underline font-medium">Edit</a>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
