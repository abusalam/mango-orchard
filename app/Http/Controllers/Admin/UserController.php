<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Permissions;
use App\Roles;
use App\Telemetry\Telemetry;
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

        $before = $user->roles->pluck('name')->all();
        $after = $data['roles'] ?? [];
        $user->syncRoles($after);

        sort($before);
        $afterSorted = $after;
        sort($afterSorted);

        if ($before !== $afterSorted) {
            app(Telemetry::class)->record(
                Telemetry::USER_ROLES_UPDATED,
                subject: $user,
                context: [
                    'target_user_id' => $user->id,
                    'target_email' => $user->email,
                    'before' => $before,
                    'after' => $afterSorted,
                ],
            );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Updated roles for {$user->name}.");
    }

    public function deactivate(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot deactivate your own account.']);
        }

        if ($user->isDeactivated()) {
            return back()->with('status', "{$user->name} is already deactivated.");
        }

        $user->forceFill(['deactivated_at' => now()])->save();

        app(Telemetry::class)->record(
            'user.deactivated',
            subject: $user,
            context: ['target_user_id' => $user->id, 'target_email' => $user->email],
        );

        return back()->with('status', "Deactivated {$user->name}. Their session ends on their next request and they can no longer sign in.");
    }

    public function reactivate(User $user): RedirectResponse
    {
        if (! $user->isDeactivated()) {
            return back()->with('status', "{$user->name} is already active.");
        }

        $user->forceFill(['deactivated_at' => null])->save();

        app(Telemetry::class)->record(
            'user.reactivated',
            subject: $user,
            context: ['target_user_id' => $user->id, 'target_email' => $user->email],
        );

        return back()->with('status', "Reactivated {$user->name}.");
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->is($user)) {
            return back()->withErrors(['user' => 'You cannot delete your own account from the admin panel.']);
        }

        // Never delete the last remaining superuser — that would lock
        // everyone out of admin permanently.
        if ($user->hasRole(Roles::SUPERUSER) && User::role(Roles::SUPERUSER)->count() <= 1) {
            return back()->withErrors(['user' => 'Cannot delete the only superuser account.']);
        }

        $name = $user->name;
        $email = $user->email;
        $id = $user->id;

        // Eloquent delete (not DB cascade alone) so the model's deleting
        // hook runs: listings delete one-by-one (wiping their image blobs)
        // and the avatar file is removed from disk.
        $user->delete();

        app(Telemetry::class)->record(
            'user.deleted',
            context: ['target_user_id' => $id, 'target_email' => $email],
        );

        return redirect()
            ->route('admin.users.index')
            ->with('status', "Deleted {$name}. Their listings, owned schemes, and personal data are gone; curated content (varieties, advisories) is kept with the author cleared.");
    }
}
