<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Permissions;
use App\Roles;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

/**
 * Admin UI for placing users in the monitoring hierarchy and tagging their
 * designations. Lists every user currently enrolled (has a profile row)
 * plus every user holding the `monitor` role who is NOT yet enrolled, so
 * an admin can wire them in.
 */
class MonitorHierarchyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_MANAGE])];
    }

    public function index(): View
    {
        $enrolled = MonitorProfile::with(['user.designations', 'parent'])
            ->get()
            ->keyBy('user_id');

        $candidates = User::role(Roles::MONITOR)
            ->whereNotIn('id', $enrolled->pluck('user_id'))
            ->orderBy('name')
            ->get();

        $allMonitors = User::role(Roles::MONITOR)->orderBy('name')->get(['id', 'name', 'email']);

        return view('scheme-monitoring::admin.hierarchy.index', [
            'enrolled' => $enrolled,
            'candidates' => $candidates,
            'allMonitors' => $allMonitors,
            'designations' => Designation::orderByDesc('level')->orderBy('name')->get(),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'parent_user_id' => [
                'nullable', 'integer', 'exists:users,id',
                function (string $attribute, mixed $value, \Closure $fail) use ($user): void {
                    if ((int) $value === $user->id) {
                        $fail('A user cannot be their own parent in the hierarchy.');
                    }
                },
            ],
            'designation_ids' => ['array'],
            'designation_ids.*' => ['integer', 'exists:monitoring_designations,id'],
        ]);

        MonitorProfile::updateOrCreate(
            ['user_id' => $user->id],
            ['parent_user_id' => $data['parent_user_id'] ?? null],
        );

        $user->designations()->sync($data['designation_ids'] ?? []);

        return back()->with('status', "Updated monitoring profile for {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        MonitorProfile::where('user_id', $user->id)->delete();
        $user->designations()->detach();

        return back()->with('status', "{$user->name} removed from the monitoring hierarchy.");
    }
}
