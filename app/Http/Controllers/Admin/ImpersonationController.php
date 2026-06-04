<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Permissions;
use App\Roles;
use App\Services\Impersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;
use RuntimeException;
use Spatie\Permission\Models\Role;

class ImpersonationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::USERS_IMPERSONATE]),
        ];
    }

    public function index(): View
    {
        $roles = Role::query()
            ->where('name', '!=', Roles::IMPERSONATOR)
            ->withCount('users')
            ->orderBy('name')
            ->get();

        return view('admin.impersonate.index', [
            'roles' => $roles,
            'users' => User::with('roles')->orderBy('name')->limit(100)->get(),
        ]);
    }

    public function impersonateUser(User $user, Impersonation $impersonation): RedirectResponse
    {
        try {
            $impersonation->start($user, reason: 'user');
        } catch (RuntimeException $e) {
            return back()->withErrors(['impersonate' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', "You are now acting as {$user->name}.");
    }

    public function impersonateRole(Role $role, Impersonation $impersonation): RedirectResponse
    {
        if ($role->name === Roles::IMPERSONATOR) {
            return back()->withErrors(['impersonate' => 'Impersonating an impersonator is not allowed (would let you escalate to anyone).']);
        }

        $actor = auth()->user();
        $target = $impersonation->firstUserWithRole($role->name, excluding: $actor);

        if ($target === null) {
            return back()->withErrors(['impersonate' => "No other user holds the {$role->name} role yet."]);
        }

        try {
            $impersonation->start($target, reason: 'role:'.$role->name);
        } catch (RuntimeException $e) {
            return back()->withErrors(['impersonate' => $e->getMessage()]);
        }

        return redirect()
            ->route('dashboard')
            ->with('status', "You are now acting as {$target->name} (any {$role->name}).");
    }
}
