<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;

it('creates a task assigned to a subordinate', function () {
    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => null]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);

    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);

    $this->actingAs($lead)
        ->post(route('monitoring.tasks.store'), [
            'scheme_id' => $scheme->id,
            'title' => 'Inspect site',
            'description' => 'Visit and report.',
            'deadline' => now()->addDays(7)->toDateString(),
            'status' => Task::STATUS_PENDING,
            'priority' => Task::PRIORITY_HIGH,
            'assigned_to' => $officer->id,
        ])
        ->assertRedirect();

    expect(Task::where('title', 'Inspect site')->first())
        ->not->toBeNull()
        ->assigned_to->toBe($officer->id)
        ->created_by->toBe($lead->id);
});

it('lets the assignee flip their own task status and stamps completed_at', function () {
    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => null]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);

    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $officer->id, 'status' => Task::STATUS_PENDING]);

    $this->actingAs($officer)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    $fresh = $task->fresh();
    expect($fresh->status)->toBe(Task::STATUS_COMPLETED);
    expect($fresh->completed_at)->not->toBeNull();
});

it('blocks a supervisor from flipping a subordinate\'s task status', function () {
    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => null]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);

    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $officer->id, 'status' => Task::STATUS_PENDING]);

    $this->actingAs($lead)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_COMPLETED])
        ->assertForbidden();

    expect($task->fresh()->status)->toBe(Task::STATUS_PENDING);
});

it('lets a monitor-admin override the assignee-only status rule', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $officer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => null]);
    $scheme = Scheme::factory()->create(['owner_id' => $officer->id]);
    $task = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $officer->id, 'status' => Task::STATUS_PENDING]);

    $this->actingAs($admin)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_IN_PROGRESS])
        ->assertRedirect();

    expect($task->fresh()->status)->toBe(Task::STATUS_IN_PROGRESS);
});

it('blocks a stranger from updating a task outside their subtree', function () {
    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    $stranger = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => null]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);
    MonitorProfile::create(['user_id' => $stranger->id, 'parent_user_id' => null]);

    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $officer->id]);

    $this->actingAs($stranger)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_COMPLETED])
        ->assertForbidden();
});
