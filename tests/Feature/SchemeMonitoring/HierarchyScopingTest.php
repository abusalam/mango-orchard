<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;

beforeEach(function (): void {
    // Build a 3-level tree via the designation chain: director -> lead -> officer
    $this->director = User::factory()->monitor()->create(['name' => 'Director']);
    $this->lead = User::factory()->monitor()->create(['name' => 'Team Lead']);
    $this->officer = User::factory()->monitor()->create(['name' => 'Field Officer']);
    $this->stranger = User::factory()->monitor()->create(['name' => 'Stranger']);

    monitorHierarchy([
        [$this->director, null],
        [$this->lead, $this->director],
        [$this->officer, $this->lead],
        [$this->stranger, null],
    ]);
});

it('returns self plus every descendant', function () {
    $h = app(Hierarchy::class);
    expect($h->descendantUserIds($this->director->id))
        ->toEqualCanonicalizing([$this->director->id, $this->lead->id, $this->officer->id]);
    expect($h->descendantUserIds($this->lead->id))
        ->toEqualCanonicalizing([$this->lead->id, $this->officer->id]);
    expect($h->descendantUserIds($this->officer->id))->toEqual([$this->officer->id]);
});

it('returns only self for a stranger with no descendants', function () {
    expect(app(Hierarchy::class)->descendantUserIds($this->stranger->id))
        ->toEqual([$this->stranger->id]);
});

it('scopes dashboard tasks to the viewer subtree', function () {
    $scheme = Scheme::factory()->create(['owner_id' => $this->director->id]);
    $directorTask = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->director->id, 'title' => 'D-task']);
    $leadTask = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->lead->id, 'title' => 'L-task']);
    $officerTask = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->officer->id, 'title' => 'O-task']);
    $strangerTask = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->stranger->id, 'title' => 'S-task']);

    // Director sees their whole subtree but NOT the stranger's task.
    $r = $this->actingAs($this->director)->get(route('monitoring.dashboard'));
    $r->assertOk()->assertSee('D-task')->assertSee('L-task')->assertSee('O-task')->assertDontSee('S-task');

    // Lead sees themselves + the officer but NOT the director above them.
    $r = $this->actingAs($this->lead)->get(route('monitoring.dashboard'));
    $r->assertOk()->assertSee('L-task')->assertSee('O-task')->assertDontSee('D-task')->assertDontSee('S-task');

    // Officer sees only their own.
    $r = $this->actingAs($this->officer)->get(route('monitoring.dashboard'));
    $r->assertOk()->assertSee('O-task')->assertDontSee('L-task')->assertDontSee('D-task');
});

it('lets a monitor-admin see all tasks regardless of hierarchy', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $scheme = Scheme::factory()->create(['owner_id' => $this->director->id]);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->stranger->id, 'title' => 'StrangerTask']);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->officer->id, 'title' => 'OfficerTask']);

    $this->actingAs($admin)
        ->get(route('monitoring.dashboard'))
        ->assertOk()
        ->assertSee('StrangerTask')
        ->assertSee('OfficerTask');
});
