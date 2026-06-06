<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleApplication;
use App\Models\User;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin entry point for enrolling users into the Mango Orchard module.
 * Holding `mango-orchard-member` is what unlocks self-apply for the
 * module's sub-roles (grower, curator, convener, advisor) on the user's
 * profile page; admins can also pre-assign sub-roles here in one go.
 *
 * Revoking module access drops the membership role AND every sub-role
 * the user held, and cancels any pending applications for those roles —
 * a clean exit so the user is back to "no module access" state.
 *
 * Gated by `users.manage` (the existing admin permission for user-side
 * grants); intentionally NOT gated by an in-module permission, because
 * by definition admins doing the enrolment may not themselves be in the
 * module.
 */
class ModuleAccessController extends Controller implements HasMiddleware
{
    private const SUB_ROLES = [
        Roles::GROWER,
        Roles::CURATOR,
        Roles::CONVENER,
        Roles::ADVISOR,
    ];

    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::USERS_MANAGE])];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $only = (string) $request->query('only', 'all');

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->when($only === 'members', fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', Roles::MANGO_ORCHARD_MEMBER)))
            ->when($only === 'non-members', fn ($q) => $q->whereDoesntHave('roles', fn ($q) => $q->where('name', Roles::MANGO_ORCHARD_MEMBER)))
            ->with('roles')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('admin.mango-orchard.access.index', [
            'users' => $users,
            'search' => $search,
            'only' => $only,
            'subRoles' => self::SUB_ROLES,
            'memberCount' => User::role(Roles::MANGO_ORCHARD_MEMBER)->count(),
        ]);
    }

    public function grant(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'sub_roles' => ['array'],
            'sub_roles.*' => ['string', 'in:'.implode(',', self::SUB_ROLES)],
        ]);

        if (! $user->hasRole(Roles::MANGO_ORCHARD_MEMBER)) {
            $user->assignRole(Roles::MANGO_ORCHARD_MEMBER);
        }

        foreach ($data['sub_roles'] ?? [] as $role) {
            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }
        }

        return back()->with('status', "{$user->name} added to the Mango Orchard module.");
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->removeRole(Roles::MANGO_ORCHARD_MEMBER);
        foreach (self::SUB_ROLES as $role) {
            $user->removeRole($role);
        }

        // Cancel any pending self-applications for module sub-roles so we
        // don't leave them flapping after the user has been kicked out.
        RoleApplication::query()
            ->where('user_id', $user->id)
            ->where('status', RoleApplication::STATUS_PENDING)
            ->whereHas('role', fn ($q) => $q->whereIn('name', [
                ...self::SUB_ROLES, Roles::MANGO_ORCHARD_MEMBER,
            ]))
            ->update(['status' => RoleApplication::STATUS_REJECTED, 'reviewed_at' => now()]);

        return back()->with('status', "{$user->name} removed from the Mango Orchard module.");
    }
}
