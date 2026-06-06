<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Permissions;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_VIEW])];
    }

    public function show(Request $request, Hierarchy $hierarchy): View
    {
        $viewer = $request->user();
        $canManage = $viewer->can(Permissions::MONITORING_MANAGE);

        $visibleUserIds = $canManage ? null : $hierarchy->descendantUserIds($viewer->id);

        // Multi-select statuses + windows from the sidebar. Both are
        // tolerant of either the legacy single-value form (`status=pending`)
        // or the new array form (`statuses[]=pending`) so older bookmarks
        // and tests don't 404 into nothing.
        $statusInputs = (array) $request->query('statuses', []);
        if ($statusInputs === [] && $request->query('status')) {
            $statusInputs = [(string) $request->query('status')];
        }
        $requestedStatuses = array_values(array_filter(
            array_map(fn ($s) => (string) $s, $statusInputs),
            fn (string $s) => isset(Task::STATUSES[$s]),
        ));

        $windowInputs = (array) $request->query('windows', []);
        if ($windowInputs === [] && $request->query('window') && $request->query('window') !== 'all') {
            $windowInputs = [(string) $request->query('window')];
        }
        $allowedWindows = ['open', 'overdue', 'today', '3day', '7day', 'upcoming'];
        $requestedWindows = array_values(array_filter(
            array_map(fn ($w) => (string) $w, $windowInputs),
            fn (string $w) => in_array($w, $allowedWindows, true),
        ));

        // Per-group include / exclude mode. Each defaults to 'include'.
        // When 'exclude', the matching items are removed from the result
        // instead of restricted to.
        $modeOf = fn (string $key) => $request->query($key) === 'exclude' ? 'exclude' : 'include';
        $statusesMode = $modeOf('statuses_mode');
        $windowsMode = $modeOf('windows_mode');
        $assigneesMode = $modeOf('assignees_mode');
        $designationsMode = $modeOf('designations_mode');

        $sort = in_array($request->query('sort'), ['deadline', 'priority', 'created'], true)
            ? (string) $request->query('sort')
            : 'deadline';
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';
        $schemeId = $request->query('scheme');

        // Sidebar assignee filter — multi-select via `assignees[]=ID`. We
        // restrict the accepted IDs to the viewer's visible subtree (or all
        // users if they're a manager) so query-string injection can't widen
        // the row scope.
        $assignableQuery = User::query()
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('id', $visibleUserIds))
            ->orderBy('name');

        $assignableUsers = $assignableQuery->with('designations')->get(['id', 'name', 'email']);
        $allowedAssigneeIds = $assignableUsers->pluck('id')->all();

        $requestedAssignees = collect((array) $request->query('assignees', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => in_array($id, $allowedAssigneeIds, true))
            ->values()
            ->all();

        // Designations whose users intersect the viewer's subtree — the
        // sidebar only lists designations actually held by someone the
        // viewer can see, so empty rows don't clutter the panel.
        $designations = Designation::query()
            ->whereHas('users', fn ($q) => $q->whereIn('users.id', $allowedAssigneeIds))
            ->orderByDesc('level')
            ->orderBy('name')
            ->with(['users' => fn ($q) => $q->whereIn('users.id', $allowedAssigneeIds)])
            ->get();

        $requestedDesignations = collect((array) $request->query('designations', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $designations->contains('id', $id))
            ->values()
            ->all();

        // Designation IDs expand to user IDs (every user holding any of
        // the selected designations within the viewer's subtree).
        $designationUserIds = $designations
            ->filter(fn (Designation $d) => in_array($d->id, $requestedDesignations, true))
            ->flatMap(fn (Designation $d) => $d->users->pluck('id'))
            ->unique()
            ->values()
            ->all();

        $tasks = Task::query()
            // Eager-load attachments on both the task and its scheme so the
            // dashboard's per-card attachment chips don't trigger N+1.
            ->with(['scheme.attachments', 'assignee', 'attachments'])
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('assigned_to', $visibleUserIds))
            // Assignees + designations now apply INDEPENDENTLY (AND'd),
            // each respecting its own include/exclude mode. Previously they
            // were unioned (OR), which made mixed include/exclude semantics
            // ambiguous.
            ->when($requestedAssignees !== [], fn ($q) => $assigneesMode === 'exclude'
                ? $q->whereNotIn('assigned_to', $requestedAssignees)
                : $q->whereIn('assigned_to', $requestedAssignees))
            ->when($requestedDesignations !== [], fn ($q) => $designationsMode === 'exclude'
                ? $q->whereNotIn('assigned_to', $designationUserIds)
                : $q->whereIn('assigned_to', $designationUserIds))
            ->when($requestedStatuses !== [], fn ($q) => $statusesMode === 'exclude'
                ? $q->whereNotIn('status', $requestedStatuses)
                : $q->whereIn('status', $requestedStatuses))
            ->when($schemeId, fn ($q) => $q->where('scheme_id', $schemeId))
            // Windows are unioned (OR) within the group; the group's mode
            // then either WHEREs that union or wraps it in WHERE NOT.
            ->when($requestedWindows !== [], function ($q) use ($requestedWindows, $windowsMode) {
                $orClause = function ($q) use ($requestedWindows) {
                    foreach ($requestedWindows as $window) {
                        $q->orWhere(function ($q) use ($window) {
                            $q->whereIn('status', Task::OPEN_STATUSES);
                            match ($window) {
                                'overdue' => $q->whereDate('deadline', '<', now()),
                                'today' => $q->whereDate('deadline', '=', now()),
                                '3day' => $q->whereDate('deadline', '>=', now())
                                    ->whereDate('deadline', '<=', now()->addDays(3)),
                                '7day' => $q->whereDate('deadline', '>=', now())
                                    ->whereDate('deadline', '<=', now()->addDays(7)),
                                'upcoming' => $q->whereDate('deadline', '>=', now())
                                    ->whereDate('deadline', '<=', now()->addDays(14)),
                                'open' => $q, // already restricted to OPEN_STATUSES above
                                default => $q,
                            };
                        });
                    }
                };
                if ($windowsMode === 'exclude') {
                    $q->whereNot($orClause);
                } else {
                    $q->where($orClause);
                }
            })
            ->orderBy(match ($sort) {
                'priority' => 'priority',
                'created' => 'created_at',
                default => 'deadline',
            }, $direction)
            ->paginate(20)
            ->withQueryString();

        // Sidebar's per-assignee task count — single GROUP BY over the same
        // subtree so the counts respect hierarchy scoping but ignore the
        // current per-assignee filter (otherwise selecting one would zero
        // the others out and the sidebar becomes useless).
        $taskCountsByAssignee = Task::query()
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('assigned_to', $visibleUserIds))
            ->select('assigned_to', DB::raw('COUNT(*) as c'))
            ->groupBy('assigned_to')
            ->pluck('c', 'assigned_to')
            ->all();

        $stats = $this->computeStats($visibleUserIds);

        $schemes = Scheme::query()
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('owner_id', $visibleUserIds))
            ->orderBy('name')
            ->get(['id', 'name']);

        // Per-designation task count — same rationale as the per-assignee
        // count: respect subtree scoping but ignore the current selection
        // so the sidebar numbers stay informative when something's checked.
        $taskCountsByDesignation = [];
        foreach ($designations as $designation) {
            $taskCountsByDesignation[$designation->id] = $designation->users
                ->sum(fn (User $u) => $taskCountsByAssignee[$u->id] ?? 0);
        }

        return view('scheme-monitoring::dashboard', [
            'tasks' => $tasks,
            'stats' => $stats,
            'schemes' => $schemes,
            'assignableUsers' => $assignableUsers,
            'taskCountsByAssignee' => $taskCountsByAssignee,
            'designations' => $designations,
            'taskCountsByDesignation' => $taskCountsByDesignation,
            'filters' => [
                'sort' => $sort,
                'direction' => $direction,
                'scheme' => $schemeId,
                'statuses' => $requestedStatuses,
                'statuses_mode' => $statusesMode,
                'windows' => $requestedWindows,
                'windows_mode' => $windowsMode,
                'assignees' => $requestedAssignees,
                'assignees_mode' => $assigneesMode,
                'designations' => $requestedDesignations,
                'designations_mode' => $designationsMode,
            ],
            'canManage' => $canManage,
        ]);
    }

    /**
     * @param  list<int>|null  $visibleUserIds  null = global (manager) view
     * @return array<string, int>
     */
    private function computeStats(?array $visibleUserIds): array
    {
        $base = fn () => Task::query()
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('assigned_to', $visibleUserIds));

        return [
            'open' => (clone $base())->whereIn('status', Task::OPEN_STATUSES)->count(),
            'overdue' => (clone $base())->whereIn('status', Task::OPEN_STATUSES)
                ->whereDate('deadline', '<', now())->count(),
            'due_this_week' => (clone $base())->whereIn('status', Task::OPEN_STATUSES)
                ->whereDate('deadline', '>=', now())
                ->whereDate('deadline', '<=', now()->addDays(7))
                ->count(),
            'completed' => (clone $base())->where('status', Task::STATUS_COMPLETED)->count(),
        ];
    }
}
