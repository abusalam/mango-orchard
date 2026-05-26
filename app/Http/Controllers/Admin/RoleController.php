<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRoleRequest;
use App\Http\Requests\UpdateRoleRequest;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::ROLES_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.roles.index', [
            'roles' => Role::with('permissions')->orderBy('name')->get(),
            'permissionsCatalog' => Permissions::ALL,
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'role' => new Role,
            'permissions' => Permission::orderBy('name')->get(),
            'permissionLabels' => Permissions::ALL,
        ]);
    }

    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create(['name' => $request->validated('name'), 'guard_name' => 'web']);
        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Created role {$role->name}.");
    }

    public function edit(Role $role): View
    {
        return view('admin.roles.edit', [
            'role' => $role->load('permissions'),
            'permissions' => Permission::orderBy('name')->get(),
            'permissionLabels' => Permissions::ALL,
        ]);
    }

    public function update(UpdateRoleRequest $request, Role $role): RedirectResponse
    {
        abort_if($role->name === Roles::SUPERUSER, 403, 'The superuser role cannot be modified.');

        $role->update(['name' => $request->validated('name')]);
        $role->syncPermissions($request->validated('permissions', []));

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Updated role {$role->name}.");
    }

    public function destroy(Role $role): RedirectResponse
    {
        abort_if($role->name === Roles::SUPERUSER, 403, 'The superuser role cannot be deleted.');
        abort_if($role->users()->exists(), 422, 'This role is still assigned to one or more users.');

        $name = $role->name;
        $role->delete();

        return redirect()
            ->route('admin.roles.index')
            ->with('status', "Removed role {$name}.");
    }
}
