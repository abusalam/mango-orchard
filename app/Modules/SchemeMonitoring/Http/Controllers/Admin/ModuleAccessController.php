<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin entry point for enrolling users into the scheme/project monitoring
 * module. Grant = assigns the `monitor` role AND creates the profile row
 * (no parent yet — admin places them in the tree from the hierarchy page).
 * Revoke = drops both monitor roles, deletes the profile, detaches any
 * designations. Membership is the gate — without a profile the user can
 * still hold the role but won't appear anywhere in the visibility scopes.
 *
 * Only `monitoring.manage` holders see this page; monitor roles are not
 * self-applicable or peer-delegatable (see [App\Roles]), so this UI is
 * the single path into the module.
 */
class ModuleAccessController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_MANAGE])];
    }

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $only = (string) $request->query('only', 'all'); // all | members | non-members

        $users = User::query()
            ->when($search !== '', fn ($q) => $q->where(function ($q) use ($search): void {
                $q->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%");
            }))
            ->when($only === 'members', fn ($q) => $q->whereHas('roles', fn ($q) => $q->where('name', Roles::MONITOR)))
            ->when($only === 'non-members', fn ($q) => $q->whereDoesntHave('roles', fn ($q) => $q->where('name', Roles::MONITOR)))
            ->with(['roles', 'monitoringProfile'])
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('scheme-monitoring::admin.access.index', [
            'users' => $users,
            'search' => $search,
            'only' => $only,
            'memberCount' => User::role(Roles::MONITOR)->count(),
        ]);
    }

    public function grant(User $user): RedirectResponse
    {
        if (! $user->hasRole(Roles::MONITOR)) {
            $user->assignRole(Roles::MONITOR);
        }

        MonitorProfile::firstOrCreate(
            ['user_id' => $user->id],
            ['parent_user_id' => null],
        );

        return back()->with('status', "{$user->name} added to the Monitoring module.");
    }

    public function revoke(User $user): RedirectResponse
    {
        $user->removeRole(Roles::MONITOR);
        $user->removeRole(Roles::MONITOR_ADMIN);

        MonitorProfile::where('user_id', $user->id)->delete();
        $user->designations()->detach();

        // Re-parent any direct reports whose parent was this user so we
        // don't leave dangling references in the tree.
        MonitorProfile::where('parent_user_id', $user->id)->update(['parent_user_id' => null]);

        return back()->with('status', "{$user->name} removed from the Monitoring module.");
    }
}
