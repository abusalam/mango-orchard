<x-admin-layout :title="'Edit '.$role->name" active="roles">
    <header class="mb-6">
        <p class="text-sm text-stone-500"><a href="{{ route('admin.roles.index') }}" class="hover:text-stone-900">Roles</a> / Edit</p>
        <h1 class="mt-2 text-3xl font-semibold tracking-tight">Edit role: {{ $role->name }}</h1>
    </header>

    @include('admin.roles._form', [
        'role' => $role,
        'action' => route('admin.roles.update', $role),
        'method' => 'PUT',
        'permissions' => $permissions,
        'permissionLabels' => $permissionLabels,
    ])
</x-admin-layout>
