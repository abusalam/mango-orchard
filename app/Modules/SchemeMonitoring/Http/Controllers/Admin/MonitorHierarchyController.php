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
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Admin UI for tagging users with designations. Reporting structure lives
 * on the designation tree (Designations admin page); this page only ties
 * users to designations and surfaces the resulting effective parents as
 * read-only chips so the admin can see whose visibility they affect.
 */
class MonitorHierarchyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_MANAGE])];
    }

    public function index(): View
    {
        $enrolled = MonitorProfile::with('user.designations')
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
            'effectiveParentsByUserId' => $this->effectiveParents($allMonitors->pluck('id')->all()),
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'designation_ids' => ['array'],
            'designation_ids.*' => ['integer', 'exists:monitoring_designations,id'],
        ]);

        MonitorProfile::firstOrCreate(['user_id' => $user->id]);
        $user->designations()->sync($data['designation_ids'] ?? []);

        return back()->with('status', "Updated monitoring profile for {$user->name}.");
    }

    public function destroy(User $user): RedirectResponse
    {
        MonitorProfile::where('user_id', $user->id)->delete();
        $user->designations()->detach();

        return back()->with('status', "{$user->name} removed from the monitoring hierarchy.");
    }

    /**
     * Compute "effective reporting parents" for each given user id —
     * other users holding any designation that is the immediate parent
     * of any designation the target user holds. Returns a map keyed by
     * user id; value is a collection of ['name' => …, 'via' => …]
     * so the UI can show the via-designation as a tooltip.
     *
     * @param  list<int>  $userIds
     * @return Collection<int, Collection<int, array{name: string, via: string}>>
     */
    private function effectiveParents(array $userIds): Collection
    {
        if ($userIds === []) {
            return collect();
        }

        // Pull every (user → designation → parent-designation) edge for
        // the target users, then look up which users hold those parents.
        $rows = DB::table('monitoring_user_designations as ud')
            ->join('monitoring_designations as d', 'd.id', '=', 'ud.designation_id')
            ->join('monitoring_designations as pd', 'pd.id', '=', 'd.parent_id')
            ->whereIn('ud.user_id', $userIds)
            ->select('ud.user_id', 'd.name as child_designation', 'pd.id as parent_designation_id', 'pd.name as parent_designation')
            ->get();

        if ($rows->isEmpty()) {
            return collect();
        }

        $parentHolders = DB::table('monitoring_user_designations as ud')
            ->join('users as u', 'u.id', '=', 'ud.user_id')
            ->whereIn('ud.designation_id', $rows->pluck('parent_designation_id')->unique()->all())
            ->select('ud.designation_id', 'u.id as user_id', 'u.name')
            ->get()
            ->groupBy('designation_id');

        return $rows
            ->groupBy('user_id')
            ->map(function ($edges) use ($parentHolders) {
                $seen = [];
                $out = collect();
                foreach ($edges as $edge) {
                    foreach ($parentHolders->get($edge->parent_designation_id, collect()) as $holder) {
                        if ((int) $holder->user_id === (int) $edge->user_id) {
                            // Skip themselves — happens when the user holds
                            // both parent + child designations.
                            continue;
                        }
                        $key = $holder->user_id.':'.$edge->parent_designation_id;
                        if (isset($seen[$key])) {
                            continue;
                        }
                        $seen[$key] = true;
                        $out->push([
                            'name' => $holder->name,
                            'via' => "{$edge->child_designation} → {$edge->parent_designation}",
                        ]);
                    }
                }
                return $out;
            });
    }
}
