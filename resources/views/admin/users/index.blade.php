<x-admin-layout title="Users" active="users">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Users</h1>
            <p class="mt-1 text-stone-600">Assign roles to control what each user can do.</p>
        </div>
        <p class="text-sm text-stone-500">{{ $users->count() }} {{ Str::plural('user', $users->count()) }}</p>
    </header>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-stone-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Name</th>
                    <th class="px-5 py-3 font-medium">Email</th>
                    <th class="px-5 py-3 font-medium">Roles</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($users as $user)
                    <tr>
                        <td class="px-5 py-4 font-medium text-stone-900">{{ $user->name }}</td>
                        <td class="px-5 py-4 text-stone-600">{{ $user->email }}</td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($user->roles as $role)
                                    <span @class([
                                        'inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border',
                                        'bg-amber-100 text-amber-900 border-amber-200' => $role->name === \App\Roles::SUPERUSER,
                                        'bg-stone-100 text-stone-700 border-stone-200' => $role->name !== \App\Roles::SUPERUSER,
                                    ])>{{ $role->name }}</span>
                                @empty
                                    <span class="text-stone-400 italic">no roles</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            <a href="{{ route('admin.users.edit', $user) }}" class="text-orange-700 hover:text-orange-900 font-medium">Edit</a>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
