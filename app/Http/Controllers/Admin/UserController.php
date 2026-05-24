<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class UserController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::USERS_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.users.index', [
            'users' => User::with('roles')->orderBy('name')->get(),
        ]);
    }

    public function edit(User $user): View
    {
        return view('admin.users.edit', [
            'user' => $user->load('roles'),
            'roles' => Role::orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'roles' => ['array'],
            'roles.*' => ['string', Rule::exists('roles', 'name')],
        ]);

        $isSelf = $request->user()->is($user);
        $isSuperuser = $user->hasRole(Roles::SUPERUSER);

        if ($isSelf && $isSuperuser && ! in_array(Roles::SUPERUSER, $data['roles'] ?? [], true)) {
            return back()->withErrors(['roles' => 'You cannot remove the superuser role from yourself.']);
        }

        $user->syncRoles($data['roles'] ?? []);

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Updated roles for {$user->name}.");
    }
}
