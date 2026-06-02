<x-admin-layout title="Roles" active="roles">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Roles &amp; permissions</h1>
            <p class="mt-1 text-stone-600">Group permissions into roles, then assign roles to users.</p>
        </div>
        <a href="{{ route('admin.roles.create') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">New role</a>
    </header>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-stone-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">Role</th>
                    <th class="px-5 py-3 font-medium">Permissions</th>
                    <th class="px-5 py-3 font-medium text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @foreach ($roles as $role)
                    @php $isSuper = $role->name === \App\Roles::SUPERUSER; @endphp
                    <tr class="odd:bg-stone-50/60 hover:bg-amber-50/60 transition-colors">
                        <td class="px-5 py-4 font-medium text-stone-900">
                            {{ $role->name }}
                            @if ($isSuper)
                                <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-100 text-amber-900 border border-amber-200">protected</span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            <div class="flex flex-wrap gap-1.5">
                                @forelse ($role->permissions as $perm)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">{{ $perm->name }}</span>
                                @empty
                                    <span class="text-stone-400 italic">no permissions</span>
                                @endforelse
                            </div>
                        </td>
                        <td class="px-5 py-4 text-right">
                            @unless ($isSuper)
                                <div class="flex justify-end gap-2 text-sm">
                                    <a href="{{ route('admin.roles.edit', $role) }}" class="text-orange-700 hover:text-orange-900 font-medium">Edit</a>
                                    <x-confirm-form
                                        :action="route('admin.roles.destroy', $role)"
                                        method="DELETE"
                                        :title="'Delete role '.$role->name.'?'"
                                        message="Anyone currently assigned this role will lose its permissions immediately."
                                        confirm-label="Delete role"
                                    >
                                        <button type="button" class="text-rose-700 hover:text-rose-900 font-medium">Delete</button>
                                    </x-confirm-form>
                                </div>
                            @endunless
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</x-admin-layout>
