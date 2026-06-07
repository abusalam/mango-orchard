<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring;

use Illuminate\Support\Facades\DB;

/**
 * Hierarchy walker for the scheme-monitoring module. Reporting structure
 * lives on `monitoring_designations.parent_id` — a self-referential
 * adjacency list — and users are tagged with one or more designations
 * via `monitoring_user_designations`. A viewer's effective reports are
 * the users holding any designation transitively descended from any
 * designation the viewer holds.
 *
 * Cycle-safe: BFS uses a `seen` map so a designation chain that loops
 * back on itself (shouldn't be possible because the controller forbids
 * cycles, but) still terminates.
 */
class Hierarchy
{
    /**
     * Return every user id the viewer can see in the monitoring module:
     * themselves + every descendant via the designation chain. Returns
     * just [$viewerId] when the viewer holds no designations or none of
     * their designations have descendants.
     *
     * @return list<int>
     */
    public function descendantUserIds(int $viewerId): array
    {
        $viewerDesignationIds = DB::table('monitoring_user_designations')
            ->where('user_id', $viewerId)
            ->pluck('designation_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($viewerDesignationIds === []) {
            return [$viewerId];
        }

        $descendantDesignationIds = $this->descendantDesignationIds($viewerDesignationIds);

        // Strip the viewer's own designations from the bucket we use to
        // collect "report" users — otherwise peers holding the same
        // designation as the viewer would appear as their reports.
        $reportDesignationIds = array_values(array_diff(
            $descendantDesignationIds,
            $viewerDesignationIds,
        ));

        $visible = [$viewerId => true];

        if ($reportDesignationIds !== []) {
            $reportUserIds = DB::table('monitoring_user_designations')
                ->whereIn('designation_id', $reportDesignationIds)
                ->pluck('user_id')
                ->map(fn ($id) => (int) $id)
                ->all();
            foreach ($reportUserIds as $id) {
                $visible[$id] = true;
            }
        }

        return array_keys($visible);
    }

    /**
     * BFS over `monitoring_designations.parent_id` starting from the
     * given seed ids; returns the seed plus every transitive descendant.
     *
     * @param  list<int>  $seedIds
     * @return list<int>
     */
    private function descendantDesignationIds(array $seedIds): array
    {
        $frontier = $seedIds;
        $seen = [];
        foreach ($seedIds as $id) {
            $seen[$id] = true;
        }

        while ($frontier !== []) {
            $children = DB::table('monitoring_designations')
                ->whereIn('parent_id', $frontier)
                ->pluck('id')
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                $childId = (int) $childId;
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;
                $frontier[] = $childId;
            }
        }

        return array_keys($seen);
    }
}
