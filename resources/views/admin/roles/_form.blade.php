@props(['role', 'action', 'method' => 'POST', 'permissions', 'permissionLabels'])

<form method="POST" action="{{ $action }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 sm:p-8 space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Role name</label>
        <input type="text" name="name" id="name" required maxlength="60"
               value="{{ old('name', $role->name) }}"
               placeholder="e.g. moderator"
               pattern="[a-z0-9._-]+"
               class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Lowercase letters, numbers, dots, hyphens, and underscores.</p>
        @error('name') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <fieldset>
        <legend class="block text-sm font-medium text-stone-800 dark:text-stone-200">Permissions</legend>
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Pick the permissions to grant to anyone with this role.</p>

        @php
            $currentlyAssigned = old('permissions', $role->exists ? $role->permissions->pluck('name')->all() : []);
        @endphp

        <div class="mt-4 grid sm:grid-cols-2 gap-3">
            @foreach ($permissions as $perm)
                <label class="flex items-start gap-3 p-3 rounded-xl border border-stone-200 dark:border-stone-800 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
                    <input type="checkbox" name="permissions[]" value="{{ $perm->name }}"
                           @checked(in_array($perm->name, $currentlyAssigned, true))
                           class="mt-1 rounded text-orange-500 focus:ring-orange-400">
                    <span>
                        <span class="block font-medium text-stone-900 dark:text-stone-100 text-sm">{{ $perm->name }}</span>
                        <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">{{ $permissionLabels[$perm->name] ?? '' }}</span>
                    </span>
                </label>
            @endforeach
        </div>
        @error('permissions') <p class="mt-2 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </fieldset>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100 dark:border-stone-800">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save role' }}
        </button>
        <a href="{{ route('admin.roles.index') }}" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100">Cancel</a>
    </div>
</form>
