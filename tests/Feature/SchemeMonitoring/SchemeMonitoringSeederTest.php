<?php

declare(strict_types=1);

use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use Database\Seeders\SchemeMonitoringSeeder;

/**
 * Smoke test for the demo seeder — verifies it lands the supporting
 * models AND that every deadline-bar bucket is represented at least once
 * so the dashboard demo shows the full colour ramp.
 */
function bucketFor(Task $task): string
{
    if ($task->status === Task::STATUS_COMPLETED) {
        return 'completed';
    }
    if ($task->status === Task::STATUS_CANCELLED) {
        return 'cancelled';
    }

    $start = $task->created_at->copy()->startOfDay();
    $deadline = $task->deadline->copy()->startOfDay();
    $now = now()->startOfDay();

    $totalDays = max(1, $start->diffInDays($deadline));
    $elapsedDays = max(0, (int) round($start->diffInDays($now, false)));
    $remainingDays = (int) $now->diffInDays($deadline, false);
    $progressPct = (int) round(min(100, max(0, ($elapsedDays / $totalDays) * 100)));

    if ($remainingDays < 0) {
        return 'overdue';
    }
    if ($remainingDays === 0) {
        return 'due-today';
    }
    if ($progressPct >= 90 || $remainingDays <= 1) {
        return 'critical';
    }
    if ($progressPct >= 75 || $remainingDays <= 3) {
        return 'urgent';
    }
    if ($progressPct >= 50 || $remainingDays <= 7) {
        return 'warming';
    }
    if ($progressPct >= 25) {
        return 'on-track';
    }

    return 'early';
}

it('seeds the supporting models for tasks', function () {
    (new SchemeMonitoringSeeder())->run();

    expect(Designation::count())->toBe(3);
    expect(MonitorProfile::count())->toBe(3);
    expect(Scheme::count())->toBe(2);
    expect(Task::count())->toBeGreaterThan(0);
});

it('hits every deadline-bar bucket at least once', function () {
    (new SchemeMonitoringSeeder())->run();

    $buckets = Task::all()->map(fn (Task $t) => bucketFor($t))->countBy();

    foreach ([
        'early',
        'on-track',
        'warming',
        'urgent',
        'critical',
        'due-today',
        'overdue',
        'completed',
        'cancelled',
    ] as $expected) {
        expect($buckets->get($expected, 0))->toBeGreaterThan(0, "Bucket {$expected} had no tasks");
    }
});

it('only produces tasks with windows between 3 and 60 days', function () {
    (new SchemeMonitoringSeeder())->run();

    foreach (Task::all() as $task) {
        $start = $task->created_at->copy()->startOfDay();
        $deadline = $task->deadline->copy()->startOfDay();
        $window = max(1, $start->diffInDays($deadline));
        expect($window)->toBeGreaterThanOrEqual(3, "Task {$task->title} had window {$window}d (below 3d floor)")
            ->toBeLessThanOrEqual(60, "Task {$task->title} had window {$window}d (above 60d ceiling)");
    }
});

it('skips itself when monitoring data already exists', function () {
    (new SchemeMonitoringSeeder())->run();
    $schemeCount = Scheme::count();
    $taskCount = Task::count();

    (new SchemeMonitoringSeeder())->run();

    expect(Scheme::count())->toBe($schemeCount);
    expect(Task::count())->toBe($taskCount);
});
