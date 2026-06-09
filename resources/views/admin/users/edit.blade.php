<x-admin-layout :title="'Edit '.$user->name" active="users">
    <header class="mb-6">
        <p class="text-sm text-stone-500 dark:text-stone-400"><a href="{{ route('admin.users.index') }}" class="hover:text-stone-900 dark:text-stone-100">Users</a> / Edit</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">{{ $user->name }}</h1>
        <p class="mt-1 text-stone-600 dark:text-stone-300">{{ $user->email }}</p>
    </header>

    <form method="POST" action="{{ route('admin.users.update', $user) }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 sm:p-8 space-y-6">
        @csrf
        @method('PUT')

        <fieldset>
            <legend class="block text-sm font-medium text-stone-800 dark:text-stone-200">Roles</legend>
            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Pick one or more roles. Permissions are inherited from each assigned role.</p>

            <div class="mt-4 space-y-2">
                @foreach ($roles as $role)
                    @php
                        $isAssigned = $user->hasRole($role->name);
                    @endphp
                    <label class="flex items-start gap-3 p-3 rounded-xl border border-stone-200 dark:border-stone-800 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                        <input type="checkbox" name="roles[]" value="{{ $role->name }}"
                               @checked(in_array($role->name, old('roles', $user->roles->pluck('name')->all()), true))
                               class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                        <span>
                            <span class="block font-medium text-stone-900 dark:text-stone-100">{{ $role->name }}</span>
                            <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">
                                @forelse ($role->permissions as $perm)
                                    <span class="inline-block mr-2">{{ $perm->name }}</span>
                                @empty
                                    <span class="italic">no permissions</span>
                                @endforelse
                            </span>
                        </span>
                    </label>
                @endforeach
            </div>
            @error('roles') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </fieldset>

        <div class="flex items-center gap-3 pt-4 border-t border-stone-100 dark:border-stone-800">
            <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                Save roles
            </button>
            <a href="{{ route('admin.users.index') }}" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100">Cancel</a>
        </div>
    </form>
</x-admin-layout>
