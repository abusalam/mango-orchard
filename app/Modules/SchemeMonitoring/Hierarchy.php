<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring;

use Illuminate\Support\Facades\DB;

/**
 * Hierarchy walker for the scheme-monitoring module's adjacency-list
 * (`monitoring_profiles.parent_user_id`). Iterative BFS — handles
 * arbitrary depth without exhausting the PHP stack on a recursive call,
 * and short-circuits cleanly if a row's parent has been deleted.
 *
 * Self-cycles can't happen because parent_user_id is constrained to
 * users.id, not to monitoring_profiles.id — a manager is identified by
 * their User row, not their profile row.
 */
class Hierarchy
{
    /**
     * Return every user id the given viewer can see in the monitoring
     * module: themselves + every descendant in the profile tree.
     *
     * Returns just [$viewerId] when the viewer has no descendants — they
     * still see their own tasks.
     *
     * @return list<int>
     */
    public function descendantUserIds(int $viewerId): array
    {
        $visible = [$viewerId];
        $frontier = [$viewerId];
        $seen = [$viewerId => true];

        while ($frontier !== []) {
            $children = DB::table('monitoring_profiles')
                ->whereIn('parent_user_id', $frontier)
                ->pluck('user_id')
                ->all();

            $frontier = [];
            foreach ($children as $childId) {
                $childId = (int) $childId;
                if (isset($seen[$childId])) {
                    continue;
                }
                $seen[$childId] = true;
                $visible[] = $childId;
                $frontier[] = $childId;
            }
        }

        return $visible;
    }
}
