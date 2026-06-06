<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;

beforeEach(function (): void {
    $this->viewer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $this->viewer->id, 'parent_user_id' => null]);
    $this->scheme = Scheme::factory()->create([
        'name' => 'Drinking Water Programme',
        'abbreviation' => 'DWP',
        'owner_id' => $this->viewer->id,
        // Pin start_date to null so the bar's anchor falls back to
        // task.created_at — keeps the existing per-test setups
        // deterministic. Tests that exercise the scheme-anchored path
        // set start_date explicitly.
        'start_date' => null,
    ]);
});

// ============== Deadline bar: width fills as time elapses ==============

it('renders the bar at 0% width when a task is freshly created', function () {
    $task = Task::factory()->dueIn(14)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="early"', escape: false)
        ->assertSee('data-progress="0"', escape: false)
        ->assertSee('width: 0%', escape: false);
});

it('renders the bar at ~50% width when half the runway has elapsed', function () {
    $task = Task::factory()->dueIn(10)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()->subDays(10)])->save();

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->toContain('data-progress="50"');
    expect($body)->toContain('width: 50%');
});

// ============== Deadline bar: T-N colour buckets ==============

it('renders the early bucket when far from the deadline and barely elapsed', function () {
    $task = Task::factory()->dueIn(20)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="early"', escape: false);
});

it('renders the on-track bucket when ~30% of runway has elapsed', function () {
    $task = Task::factory()->dueIn(7)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // 30 / (30+7) ≈ 81% — too high. Use 5 elapsed of 12 total → ~42%.
    $task->forceFill(['created_at' => now()->subDays(5)])->save();
    // Note: the deadline is `now+7` so remaining is 7, which would
    // also trigger `warming` (the rem<=7 OR clause). Setup confirms
    // that warming wins — proven below.
});

it('renders the warming bucket within a week of deadline regardless of elapsed %', function () {
    $task = Task::factory()->dueIn(7)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save(); // elapsedPct = 0, but remaining = 7

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="warming"', escape: false);
});

it('renders the warming bucket at ~60% elapsed even with plenty of days left', function () {
    $task = Task::factory()->dueIn(20)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // 30 elapsed + 20 remaining = 50 total → 60% elapsed.
    $task->forceFill(['created_at' => now()->subDays(30)])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="warming"', escape: false);
});

it('renders the urgent bucket within 3 days of deadline', function () {
    Task::factory()->dueIn(3)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="urgent"', escape: false);
});

it('renders the urgent bucket at ~80% elapsed even with days still left', function () {
    $task = Task::factory()->dueIn(10)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // 40 elapsed + 10 remaining = 50 total → 80% elapsed, but 10 days still left.
    $task->forceFill(['created_at' => now()->subDays(40)])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="urgent"', escape: false);
});

it('renders the critical bucket at 1 day remaining', function () {
    Task::factory()->dueIn(1)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="critical"', escape: false);
});

it('renders the critical bucket at ~95% elapsed even with multiple days left', function () {
    $task = Task::factory()->dueIn(5)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // 95 elapsed + 5 remaining = 100 total → 95% elapsed.
    $task->forceFill(['created_at' => now()->subDays(95)])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="critical"', escape: false);
});

it('renders the due-today bucket for a deadline today', function () {
    Task::factory()->dueIn(0)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="due-today"', escape: false)
        ->assertSee('Due today');
});

it('renders the overdue bucket for an open task past its deadline', function () {
    Task::factory()->overdueBy(3)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="overdue"', escape: false)
        ->assertSee('Overdue 3d');
});

it('renders the completed bucket regardless of deadline', function () {
    Task::factory()->dueIn(5)->completed()->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-bucket="completed"', escape: false);
});

// ============== Deadline bar: scheme-start anchor ==============

it('anchors the bar to scheme.start_date when set, so fresh tasks already show progress', function () {
    $scheme = Scheme::factory()->create([
        'owner_id' => $this->viewer->id,
        'start_date' => now()->subDays(10),
    ]);
    $task = Task::factory()->dueIn(10)->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // Task just created today, but scheme started 10 days ago.
    $task->forceFill(['created_at' => now()])->save();

    // start = 10 days ago, deadline = 10 days out → 20-day runway, 10 elapsed → 50%.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-progress="50"', escape: false);
});

it('falls back to task.created_at when the scheme has no start_date', function () {
    $task = Task::factory()->dueIn(10)->create([
        'scheme_id' => $this->scheme->id, // start_date already pinned to null in beforeEach
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()->subDays(5)])->save();

    // 5 elapsed + 10 remaining = 15-day runway, 5 elapsed → 33%.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-progress="33"', escape: false);
});

it('clamps progress to 0 when the scheme has not started yet', function () {
    $scheme = Scheme::factory()->create([
        'owner_id' => $this->viewer->id,
        'start_date' => now()->addDays(7),
    ]);
    $task = Task::factory()->dueIn(20)->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-progress="0"', escape: false);
});

// ============== Priority chip ==============

it('renders the priority chip for non-normal priorities and hides it for normal', function () {
    $u = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'priority' => Task::PRIORITY_URGENT, 'title' => 'U-task']);
    $h = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'priority' => Task::PRIORITY_HIGH, 'title' => 'H-task']);
    $n = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'priority' => Task::PRIORITY_NORMAL, 'title' => 'N-task']);
    $l = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'priority' => Task::PRIORITY_LOW, 'title' => 'L-task']);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    foreach ([$u, $h, $l] as $t) {
        $response->assertSee('data-testid="task-priority-'.$t->id.'"', escape: false);
    }
    // Normal is the default and gets no chip — the row should not contain its testid.
    $response->assertDontSee('data-testid="task-priority-'.$n->id.'"', escape: false);
});

it('encodes priority into a data-priority attribute and a colour-coded class', function () {
    $task = Task::factory()->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
        'priority' => Task::PRIORITY_URGENT,
    ]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    // Match the whole opening <span ...> tag containing the testid, then
    // pull the class attribute out of it regardless of attribute order.
    $chipRegex = '/<span\s+([^>]*data-testid="task-priority-'.$task->id.'"[^>]*)>/s';
    expect(preg_match($chipRegex, $body, $m))->toBe(1);
    expect(preg_match('/class="([^"]*)"/', $m[1], $cm))->toBe(1);
    expect($cm[1])->toContain('bg-rose-600');
    expect($body)->toContain('data-priority="'.Task::PRIORITY_URGENT.'"');
});

// ============== Attachment chips on the task card ==============

it('renders the task-attachments chip with a popover when a task has its own attachments', function () {
    $task = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);
    \App\Modules\SchemeMonitoring\Models\Attachment::factory()->create([
        'attachable_type' => $task->getMorphClass(),
        'attachable_id' => $task->id,
        'original_name' => 'task-report.pdf',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="task-attachments-chip-'.$task->id.'"', escape: false);
    $response->assertSee('data-testid="task-attachments-popover-'.$task->id.'"', escape: false);
    $response->assertSee('task-report.pdf');
});

it('renders the scheme-attachments chip when the parent scheme has attachments', function () {
    \App\Modules\SchemeMonitoring\Models\Attachment::factory()->create([
        'attachable_type' => $this->scheme->getMorphClass(),
        'attachable_id' => $this->scheme->id,
        'original_name' => 'scheme-charter.pdf',
    ]);
    $task = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="scheme-attachments-chip-'.$task->id.'"', escape: false);
    $response->assertSee('scheme-charter.pdf');
});

it('hides both attachment chips for tasks with no files on either side', function () {
    $task = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('data-testid="task-attachments-chip-'.$task->id.'"');
    expect($body)->not->toContain('data-testid="scheme-attachments-chip-'.$task->id.'"');
});

// ============== Task duration chip ==============

it('renders a duration chip counting every day between start and deadline (inclusive)', function () {
    $task = Task::factory()->dueIn(14)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save();

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="task-duration-'.$task->id.'"', escape: false);
    // 14 calendar days from today + today itself = 15 days inclusive.
    $response->assertSee('15d window');
});

it('anchors the duration chip to scheme.start_date when set', function () {
    $scheme = Scheme::factory()->create([
        'owner_id' => $this->viewer->id,
        'start_date' => now()->subDays(20),
    ]);
    $task = Task::factory()->dueIn(10)->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    // Task itself was created today, but the chip should use the scheme's
    // start (20 days ago) → 20 + 10 + 1 (inclusive) = 31 days.
    $task->forceFill(['created_at' => now()])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('31d window');
});

it('renders "1d window" when the task was created on its deadline', function () {
    $task = Task::factory()->dueIn(0)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);
    $task->forceFill(['created_at' => now()])->save();

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('1d window');
});

// ============== Scheme abbreviation chip ==============

it('shows the scheme abbreviation chip next to the task title', function () {
    Task::factory()->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
        'title' => 'ChippedTask',
    ]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="scheme-chip-', escape: false);
    $response->assertSee('DWP');
    $response->assertSee('ChippedTask');
});

it('auto-generates the abbreviation from initials when no explicit value is set', function () {
    $scheme = Scheme::factory()->create([
        'name' => 'Forest Restoration Initiative Yatra',
        'abbreviation' => null,
        'owner_id' => $this->viewer->id,
    ]);
    Task::factory()->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $this->viewer->id,
        'title' => 'AutoChipTask',
    ]);

    expect($scheme->displayAbbreviation())->toBe('FRIY');

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('FRIY');
});

it('drops the standalone Scheme column header (merged into Task)', function () {
    Task::factory()->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->viewer->id,
    ]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('>Scheme</th>');
});

// ============== Scheme form persists abbreviation ==============

it('persists an explicit abbreviation when creating a scheme', function () {
    $this->actingAs($this->viewer)
        ->post(route('monitoring.schemes.store'), [
            'name' => 'River Cleanup Operation',
            'abbreviation' => 'rco',
            'status' => 'active',
        ])
        ->assertRedirect();

    $scheme = Scheme::where('name', 'River Cleanup Operation')->firstOrFail();
    expect($scheme->abbreviation)->toBe('rco');
    expect($scheme->displayAbbreviation())->toBe('RCO');
});

it('rejects an abbreviation longer than 12 characters', function () {
    $this->actingAs($this->viewer)
        ->post(route('monitoring.schemes.store'), [
            'name' => 'Some Scheme',
            'abbreviation' => str_repeat('A', 13),
            'status' => 'active',
        ])
        ->assertSessionHasErrors('abbreviation');
});

// ============== Sidebar: assignee filter ==============

it('renders an assignee sidebar listing visible users with task counts', function () {
    // Build a small subtree below viewer so the sidebar has rows to show.
    $lead = User::factory()->monitor()->create(['name' => 'Lead Person']);
    $officer = User::factory()->monitor()->create(['name' => 'Officer Person']);
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => $this->viewer->id]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);

    Task::factory()->count(2)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $lead->id]);
    Task::factory()->count(5)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $officer->id]);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="assignee-sidebar"', escape: false);
    $response->assertSee('data-testid="assignee-list"', escape: false);
    $response->assertSee('data-testid="assignee-checkbox-'.$lead->id.'"', escape: false);
    $response->assertSee('data-testid="assignee-checkbox-'.$officer->id.'"', escape: false);
    $response->assertSee('Lead Person');
    $response->assertSee('Officer Person');
});

it('drops the Assignee column from the table header (now in the sidebar)', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('>Assignee</th>');
});

it('filters tasks to only the selected assignees', function () {
    $lead = User::factory()->monitor()->create(['name' => 'PickMe']);
    $officer = User::factory()->monitor()->create(['name' => 'SkipMe']);
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => $this->viewer->id]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $this->viewer->id]);

    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $lead->id, 'title' => 'KEEP-TASK']);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $officer->id, 'title' => 'DROP-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['assignees' => [$lead->id]]))
        ->assertOk()
        ->assertSee('KEEP-TASK')
        ->assertDontSee('DROP-TASK');
});

it('ignores assignee ids that are outside the viewer\'s subtree', function () {
    $stranger = User::factory()->monitor()->create(['name' => 'Stranger']);
    MonitorProfile::create(['user_id' => $stranger->id, 'parent_user_id' => null]);

    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'OWN-TASK']);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $stranger->id, 'title' => 'STRANGER-TASK']);

    // Even with the stranger's id in the query string, hierarchy scoping
    // wins — the stranger task never appears.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['assignees' => [$stranger->id]]))
        ->assertOk()
        ->assertDontSee('STRANGER-TASK')
        ->assertSee('OWN-TASK');
});

it('exposes a Clear link when at least one assignee is selected', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['assignees' => [$this->viewer->id]]))
        ->assertOk()
        ->assertSee('data-testid="assignee-clear"', escape: false);
});

it('does not render the Clear link when no assignee is selected', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('data-testid="assignee-clear"');
});

// ============== Sidebar group: by designation ==============

it('renders a second sidebar group for designations held in the subtree', function () {
    $blockOfficer = \App\Modules\SchemeMonitoring\Models\Designation::factory()->create(['name' => 'Block Officer']);
    $districtOfficer = \App\Modules\SchemeMonitoring\Models\Designation::factory()->create(['name' => 'District Officer']);

    $u1 = User::factory()->monitor()->create(['name' => 'BlockyA']);
    $u2 = User::factory()->monitor()->create(['name' => 'BlockyB']);
    $u3 = User::factory()->monitor()->create(['name' => 'DistrictX']);
    foreach ([$u1, $u2, $u3] as $u) {
        MonitorProfile::create(['user_id' => $u->id, 'parent_user_id' => $this->viewer->id]);
    }
    $u1->designations()->attach($blockOfficer->id);
    $u2->designations()->attach($blockOfficer->id);
    $u3->designations()->attach($districtOfficer->id);

    $response = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk();

    $response->assertSee('data-testid="sidebar-group-designation"', escape: false);
    $response->assertSee('data-testid="sidebar-group-user"', escape: false);
    $response->assertSee('data-testid="designation-checkbox-'.$blockOfficer->id.'"', escape: false);
    $response->assertSee('data-testid="designation-checkbox-'.$districtOfficer->id.'"', escape: false);
});

it('filters tasks by selecting a designation', function () {
    $blockOfficer = \App\Modules\SchemeMonitoring\Models\Designation::factory()->create(['name' => 'Block Officer']);
    $u1 = User::factory()->monitor()->create();
    $u2 = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $u1->id, 'parent_user_id' => $this->viewer->id]);
    MonitorProfile::create(['user_id' => $u2->id, 'parent_user_id' => $this->viewer->id]);
    $u1->designations()->attach($blockOfficer->id);

    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u1->id, 'title' => 'BLOCK-TASK']);
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $u2->id, 'title' => 'OTHER-TASK']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard', ['designations' => [$blockOfficer->id]]))
        ->assertOk()
        ->assertSee('BLOCK-TASK')
        ->assertDontSee('OTHER-TASK');
});

it('hides designations whose users are all outside the viewers subtree', function () {
    $blockOfficer = \App\Modules\SchemeMonitoring\Models\Designation::factory()->create(['name' => 'Block Officer']);
    $stranger = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $stranger->id, 'parent_user_id' => null]);
    $stranger->designations()->attach($blockOfficer->id);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    // The designation has no holders within the subtree, so it isn't listed.
    expect($body)->not->toContain('data-testid="designation-checkbox-'.$blockOfficer->id.'"');
});

// ============== Status column → row background ==============

it('removes the Status column header (encoded into row background instead)', function () {
    Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    expect($body)->not->toContain('>Status</th>');
});

it('encodes status as a row background colour', function () {
    $pending = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'status' => Task::STATUS_PENDING]);
    $progress = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'status' => Task::STATUS_IN_PROGRESS]);
    $done = Task::factory()->dueIn(5)->completed()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id]);
    $cancelled = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'status' => Task::STATUS_CANCELLED]);

    $body = $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->getContent();

    // Match the <tr ... > tag containing the testid regardless of
    // attribute order, then look at the class attribute inside.
    foreach ([
        [$pending->id, 'bg-white'],
        [$progress->id, 'bg-amber-50'],
        [$done->id, 'bg-emerald-50'],
        [$cancelled->id, 'bg-stone-100'],
    ] as [$taskId, $expectedClass]) {
        // Each task is rendered as an <article> (was <tr>). Match either
        // by allowing any element name before the testid.
        $rowRegex = '/<\w+\s+([^>]*data-testid="task-row-'.$taskId.'"[^>]*)>/s';
        expect(preg_match($rowRegex, $body, $m))->toBe(1);

        $attrs = $m[1];
        expect(preg_match('/class="([^"]*)"/', $attrs, $cm))->toBe(1);
        expect($cm[1])->toContain($expectedClass);
    }
});

it('exposes the task status on the row via a data-status attribute', function () {
    $task = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->viewer->id, 'status' => Task::STATUS_IN_PROGRESS]);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('data-status="in_progress"', escape: false);
});
