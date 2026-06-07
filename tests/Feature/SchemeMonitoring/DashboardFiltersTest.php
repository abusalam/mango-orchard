<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;

beforeEach(function (): void {
    $this->viewer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $this->viewer->id]);
    $this->scheme = Scheme::factory()->create(['owner_id' => $this->viewer->id]);
});

it('filters tasks by status', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'PEND-TASK', 'status' => Task::STATUS_PENDING]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'DONE-TASK', 'status' => Task::STATUS_COMPLETED]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['status' => Task::STATUS_PENDING]))
        ->assertOk()
        ->assertSee('PEND-TASK')
        ->assertDontSee('DONE-TASK');
});

it('filters tasks by overdue window', function () {
    Task::factory()->overdueBy(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'OVERDUE-TASK']);
    Task::factory()->dueIn(10)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'FUTURE-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['window' => 'overdue']))
        ->assertOk()
        ->assertSee('OVERDUE-TASK')
        ->assertDontSee('FUTURE-TASK');
});

it('sorts tasks by deadline asc and desc', function () {
    Task::factory()->dueIn(1)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'EARLY-TASK']);
    Task::factory()->dueIn(20)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'LATE-TASK']);

    $asc = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['sort' => 'deadline', 'direction' => 'asc']))
        ->assertOk()->getContent();
    expect(strpos($asc, 'EARLY-TASK'))->toBeLessThan(strpos($asc, 'LATE-TASK'));

    $desc = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['sort' => 'deadline', 'direction' => 'desc']))
        ->assertOk()->getContent();
    expect(strpos($desc, 'LATE-TASK'))->toBeLessThan(strpos($desc, 'EARLY-TASK'));
});

// ============== Multi-select status + window (sidebar checkboxes) ==============

it('filters tasks by a multi-select statuses[] array', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'PEND-TASK', 'status' => Task::STATUS_PENDING]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'PROG-TASK', 'status' => Task::STATUS_IN_PROGRESS]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'DONE-TASK', 'status' => Task::STATUS_COMPLETED]);

    // Pick pending + in_progress at the same time.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['statuses' => [Task::STATUS_PENDING, Task::STATUS_IN_PROGRESS]]))
        ->assertOk()
        ->assertSee('PEND-TASK')
        ->assertSee('PROG-TASK')
        ->assertDontSee('DONE-TASK');
});

it('filters by the new today / 3day / 7day windows', function () {
    Task::factory()->dueIn(0)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'TODAY-TASK']);
    Task::factory()->dueIn(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'D2-TASK']);
    Task::factory()->dueIn(5)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'D5-TASK']);
    Task::factory()->dueIn(10)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'D10-TASK']);

    // today: only today's task
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['windows' => ['today']]))
        ->assertOk()
        ->assertSee('TODAY-TASK')
        ->assertDontSee('D2-TASK')
        ->assertDontSee('D5-TASK')
        ->assertDontSee('D10-TASK');

    // 3day: today + within next 3 (TODAY + D2)
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['windows' => ['3day']]))
        ->assertOk()
        ->assertSee('TODAY-TASK')
        ->assertSee('D2-TASK')
        ->assertDontSee('D5-TASK')
        ->assertDontSee('D10-TASK');

    // 7day: today + within next 7 (TODAY + D2 + D5)
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['windows' => ['7day']]))
        ->assertOk()
        ->assertSee('TODAY-TASK')
        ->assertSee('D2-TASK')
        ->assertSee('D5-TASK')
        ->assertDontSee('D10-TASK');
});

it('renders all six window checkboxes in the sidebar', function () {
    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    foreach (['overdue', 'today', '3day', '7day', 'upcoming', 'open'] as $window) {
        $response->assertSee('data-testid="window-checkbox-'.$window.'"', escape: false);
    }
});

it('filters tasks by a multi-select windows[] array, unioning the matches', function () {
    Task::factory()->overdueBy(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'OVERDUE-TASK']);
    Task::factory()->dueIn(5)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'UPCOMING-TASK']);
    Task::factory()->dueIn(60)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'FAR-TASK']);

    // overdue OR upcoming — far-out tasks should be excluded.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['windows' => ['overdue', 'upcoming']]))
        ->assertOk()
        ->assertSee('OVERDUE-TASK')
        ->assertSee('UPCOMING-TASK')
        ->assertDontSee('FAR-TASK');
});

it('still accepts the legacy single status query param', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'PEND-TASK', 'status' => Task::STATUS_PENDING]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'DONE-TASK', 'status' => Task::STATUS_COMPLETED]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['status' => Task::STATUS_PENDING]))
        ->assertOk()
        ->assertSee('PEND-TASK')
        ->assertDontSee('DONE-TASK');
});

it('still accepts the legacy single window query param', function () {
    Task::factory()->overdueBy(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'OVERDUE-TASK']);
    Task::factory()->dueIn(60)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'FAR-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['window' => 'overdue']))
        ->assertOk()
        ->assertSee('OVERDUE-TASK')
        ->assertDontSee('FAR-TASK');
});

// ============== Include / Exclude modes ==============

it('excludes the selected statuses when statuses_mode = exclude', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'PEND-TASK', 'status' => Task::STATUS_PENDING]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'DONE-TASK', 'status' => Task::STATUS_COMPLETED]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', [
            'statuses' => [Task::STATUS_PENDING],
            'statuses_mode' => 'exclude',
        ]))
        ->assertOk()
        ->assertSee('DONE-TASK')
        ->assertDontSee('PEND-TASK');
});

it('excludes the selected windows when windows_mode = exclude', function () {
    Task::factory()->overdueBy(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'OVERDUE-TASK']);
    Task::factory()->dueIn(60)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'FAR-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', [
            'windows' => ['overdue'],
            'windows_mode' => 'exclude',
        ]))
        ->assertOk()
        ->assertSee('FAR-TASK')
        ->assertDontSee('OVERDUE-TASK');
});

it('excludes tasks assigned to the selected users when assignees_mode = exclude', function () {
    $u1 = User::factory()->monitor()->create(['name' => 'Excluded']);
    $u2 = User::factory()->monitor()->create(['name' => 'Kept']);
    monitorHierarchy([[$this->viewer, null], [$u1, $this->viewer], [$u2, $this->viewer]]);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u1->id, 'title' => 'DROP-TASK']);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u2->id, 'title' => 'KEEP-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', [
            'assignees' => [$u1->id],
            'assignees_mode' => 'exclude',
        ]))
        ->assertOk()
        ->assertSee('KEEP-TASK')
        ->assertDontSee('DROP-TASK');
});

it('excludes tasks for users holding the selected designations when designations_mode = exclude', function () {
    $blockOfficer = \App\Modules\SchemeMonitoring\Models\Designation::factory()->create(['name' => 'Block Officer']);
    $u1 = User::factory()->monitor()->create();
    $u2 = User::factory()->monitor()->create();
    monitorHierarchy([[$this->viewer, null], [$u1, $this->viewer], [$u2, $this->viewer]]);
    $u1->designations()->attach($blockOfficer->id);

    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u1->id, 'title' => 'DROP-TASK']);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u2->id, 'title' => 'KEEP-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', [
            'designations' => [$blockOfficer->id],
            'designations_mode' => 'exclude',
        ]))
        ->assertOk()
        ->assertSee('KEEP-TASK')
        ->assertDontSee('DROP-TASK');
});

it('renders an Exclude toggle in each sidebar filter group', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    foreach (['statuses', 'windows', 'designations', 'assignees'] as $group) {
        $response->assertSee('data-testid="'.$group.'-mode-toggle"', escape: false);
    }
});

it('flags the group title with (NOT) when the group is in exclude mode', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['statuses_mode' => 'exclude']))
        ->assertOk()
        ->getContent();

    expect($body)->toContain('By status (NOT)');
});

it('renders status + window groups in the sidebar (not the top filter bar)', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="sidebar-group-status"', escape: false);
    $response->assertSee('data-testid="sidebar-group-window"', escape: false);
    $response->assertSee('data-testid="status-checkbox-'.Task::STATUS_PENDING.'"', escape: false);
    $response->assertSee('data-testid="window-checkbox-overdue"', escape: false);

    // The dropdowns that used to live in the top filter bar are gone.
    $body = $response->getContent();
    expect($body)->not->toContain('<select name="status"');
    expect($body)->not->toContain('<select name="window"');
});

it('exposes count stats on the dashboard', function () {
    Task::factory()->count(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'status' => Task::STATUS_PENDING]);
    Task::factory()->overdueBy(3)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);
    Task::factory()->completed()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-testid="stat-open"', escape: false)
        ->assertSee('data-testid="stat-overdue"', escape: false)
        ->assertSee('data-testid="stat-completed"', escape: false);
});
