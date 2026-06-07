<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;

beforeEach(function (): void {
    $this->viewer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $this->viewer->id]);
});

// ============== Schemes index search ==============

it('renders the search form on the schemes index', function () {
    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index'))
        ->assertOk()
        ->assertSee('data-testid="schemes-search-form"', escape: false)
        ->assertSee('data-testid="schemes-search-input"', escape: false);
});

it('filters schemes by name via the q query param', function () {
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Drinking Water Programme']);
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Forest Restoration']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index', ['q' => 'water']))
        ->assertOk()
        ->assertSee('Drinking Water Programme')
        ->assertDontSee('Forest Restoration');
});

it('filters schemes by abbreviation', function () {
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Drinking Water Programme', 'abbreviation' => 'DWP']);
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Forest Restoration', 'abbreviation' => 'FR']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index', ['q' => 'DWP']))
        ->assertOk()
        ->assertSee('Drinking Water Programme')
        ->assertDontSee('Forest Restoration');
});

it('filters schemes by description', function () {
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Alpha', 'description' => 'Sanitation rollout in rural blocks']);
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Beta', 'description' => 'Urban housing initiative']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index', ['q' => 'sanitation']))
        ->assertOk()
        ->assertSee('Alpha')
        ->assertDontSee('Beta');
});

it('shows a "no match" message when the schemes search comes up empty', function () {
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Solo Scheme']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index', ['q' => 'no-such-thing']))
        ->assertOk()
        ->assertSee('data-testid="schemes-empty"', escape: false)
        ->assertSee('No schemes match');
});

it('clears the schemes search via the Clear link', function () {
    Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Pickable']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.schemes.index', ['q' => 'pickable']))
        ->assertOk()
        ->assertSee('data-testid="schemes-search-clear"', escape: false);
});

// ============== Tasks index search ==============

it('renders the search form on the tasks index', function () {
    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index'))
        ->assertOk()
        ->assertSee('data-testid="tasks-search-form"', escape: false)
        ->assertSee('data-testid="tasks-search-input"', escape: false);
});

it('filters tasks by title via the q query param', function () {
    $scheme = Scheme::factory()->create(['owner_id' => $this->viewer->id]);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'Inspect block hospital']);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'Submit monthly report']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index', ['q' => 'hospital']))
        ->assertOk()
        ->assertSee('Inspect block hospital')
        ->assertDontSee('Submit monthly report');
});

it('filters tasks by the parent scheme name', function () {
    $waterScheme = Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Drinking Water Programme', 'abbreviation' => 'DWP']);
    $forestScheme = Scheme::factory()->create(['owner_id' => $this->viewer->id, 'name' => 'Forest Restoration']);

    Task::factory()->create(['scheme_id' => $waterScheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'Water-task-A']);
    Task::factory()->create(['scheme_id' => $forestScheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'Forest-task-B']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index', ['q' => 'DWP']))
        ->assertOk()
        ->assertSee('Water-task-A')
        ->assertDontSee('Forest-task-B');
});

it('filters tasks by description', function () {
    $scheme = Scheme::factory()->create(['owner_id' => $this->viewer->id]);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'TaskA', 'description' => 'Verify pump installation site']);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'TaskB', 'description' => 'Coordinate with vendor']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index', ['q' => 'pump']))
        ->assertOk()
        ->assertSee('TaskA')
        ->assertDontSee('TaskB');
});

it('shows a "no match" message when the tasks search comes up empty', function () {
    $scheme = Scheme::factory()->create(['owner_id' => $this->viewer->id]);
    Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'Solo']);

    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index', ['q' => 'no-such-thing']))
        ->assertOk()
        ->assertSee('data-testid="tasks-empty"', escape: false)
        ->assertSee('No tasks match');
});

it('still respects hierarchy scoping when searching tasks', function () {
    $stranger = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $stranger->id]);
    $strangerScheme = Scheme::factory()->create(['owner_id' => $stranger->id]);
    Task::factory()->create(['scheme_id' => $strangerScheme->id, 'assigned_to' => $stranger->id, 'title' => 'StrangerTask']);

    $ownScheme = Scheme::factory()->create(['owner_id' => $this->viewer->id]);
    Task::factory()->create(['scheme_id' => $ownScheme->id, 'assigned_to' => $this->viewer->id, 'title' => 'StrangerTaskMine']);

    // The search would match BOTH titles, but hierarchy scoping hides the
    // stranger's task even though it textually matches.
    $this->actingAs($this->viewer)
        ->get(route('monitoring.tasks.index', ['q' => 'StrangerTask']))
        ->assertOk()
        ->assertSee('StrangerTaskMine')
        ->assertDontSee('StrangerTask<');
});
