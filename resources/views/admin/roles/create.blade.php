<x-admin-layout title="New role" active="roles">
    <header class="mb-6">
        <p class="text-sm text-stone-500 dark:text-stone-400"><a href="{{ route('admin.roles.index') }}" class="hover:text-stone-900 dark:text-stone-100">Roles</a> / New</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Create a new role</h1>
    </header>

    @include('admin.roles._form', [
        'role' => $role,
        'action' => route('admin.roles.store'),
        'method' => 'POST',
        'permissions' => $permissions,
        'permissionLabels' => $permissionLabels,
    ])
</x-admin-layout>
