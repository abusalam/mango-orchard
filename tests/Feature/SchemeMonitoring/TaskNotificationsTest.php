<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Attachment;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskStatusChanged;
use App\Modules\SchemeMonitoring\Notifications\TaskUpdated;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();

    $this->lead = User::factory()->monitor()->create(['name' => 'Lead']);
    $this->officer = User::factory()->monitor()->create(['name' => 'Officer']);
    monitorHierarchy([[$this->lead, null], [$this->officer, $this->lead]]);

    $this->scheme = Scheme::factory()->create(['owner_id' => $this->lead->id]);
    $this->task = Task::factory()->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->officer->id,
        'created_by' => $this->lead->id,
        'status' => Task::STATUS_PENDING,
        'priority' => Task::PRIORITY_NORMAL,
    ]);
});

// ============== Status flip via PATCH /status ==============

it('sends TaskStatusChanged to assignee + creator (not the actor) on status flip', function () {
    $this->actingAs($this->officer)
        ->patch(route('monitoring.tasks.status', $this->task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    // Officer is the actor → only creator (lead) gets notified.
    Notification::assertSentTo($this->lead, TaskStatusChanged::class);
    Notification::assertNotSentTo($this->officer, TaskStatusChanged::class);
});

it('does NOT send a notification when status is unchanged', function () {
    $this->actingAs($this->officer)
        ->patch(route('monitoring.tasks.status', $this->task), ['status' => $this->task->status])
        ->assertRedirect();

    Notification::assertNothingSent();
});

// ============== Full-edit flow via PUT /update ==============

it('sends TaskUpdated with a per-field diff when fields change', function () {
    $this->actingAs($this->lead)
        ->put(route('monitoring.tasks.update', $this->task), [
            'scheme_id' => $this->scheme->id,
            'title' => 'Renamed title',
            'description' => $this->task->description,
            'deadline' => $this->task->deadline->toDateString(),
            'status' => $this->task->status,
            'priority' => Task::PRIORITY_HIGH,
            'assigned_to' => $this->officer->id,
        ])
        ->assertRedirect();

    // Lead is the actor → only assignee (officer) gets notified.
    Notification::assertSentTo($this->officer, TaskUpdated::class, function (TaskUpdated $n) {
        return isset($n->changes['title'])
            && isset($n->changes['priority'])
            && $n->changes['title']['to'] === 'Renamed title'
            && $n->changes['priority']['to'] === Task::PRIORITY_HIGH;
    });
});

it('skips TaskUpdated when no tracked field changed (status-only edit)', function () {
    $this->actingAs($this->lead)
        ->put(route('monitoring.tasks.update', $this->task), [
            'scheme_id' => $this->scheme->id,
            'title' => $this->task->title,
            'description' => $this->task->description,
            'deadline' => $this->task->deadline->toDateString(),
            'status' => Task::STATUS_IN_PROGRESS,
            'priority' => $this->task->priority,
            'assigned_to' => $this->task->assigned_to,
        ])
        ->assertRedirect();

    // Status flipped → TaskStatusChanged fires; no field diff → no TaskUpdated.
    Notification::assertSentTo($this->officer, TaskStatusChanged::class);
    Notification::assertNotSentTo($this->officer, TaskUpdated::class);
});

it('notifies both old and new assignee on reassignment', function () {
    $newOfficer = User::factory()->monitor()->create(['name' => 'NewOfficer']);

    $this->actingAs($this->lead)
        ->put(route('monitoring.tasks.update', $this->task), [
            'scheme_id' => $this->scheme->id,
            'title' => $this->task->title,
            'description' => $this->task->description,
            'deadline' => $this->task->deadline->toDateString(),
            'status' => $this->task->status,
            'priority' => $this->task->priority,
            'assigned_to' => $newOfficer->id,
        ])
        ->assertRedirect();

    // Old assignee (officer), new assignee (newOfficer), and creator (lead)
    // — minus the actor (lead) — should be (officer, newOfficer).
    Notification::assertSentTo($this->officer, TaskUpdated::class);
    Notification::assertSentTo($newOfficer, TaskUpdated::class);
    Notification::assertNotSentTo($this->lead, TaskUpdated::class);
});

// ============== Email content: attachments + diff ==============

it('lists attachments in the TaskStatusChanged email body', function () {
    Attachment::factory()->create([
        'attachable_type' => $this->task->getMorphClass(),
        'attachable_id' => $this->task->id,
        'uploaded_by' => $this->officer->id,
        'original_name' => 'inspection.pdf',
        'path' => 'monitoring-attachments/x-inspection.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 12_345,
    ]);

    $this->actingAs($this->officer)
        ->patch(route('monitoring.tasks.status', $this->task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    Notification::assertSentTo($this->lead, TaskStatusChanged::class, function (TaskStatusChanged $n) {
        $mail = $n->toMail($this->lead);
        $rendered = collect($mail->introLines)->implode("\n");

        return str_contains($rendered, 'inspection.pdf')
            && str_contains($rendered, 'Attachments:');
    });
});

it('renders status transition in the email subject + body', function () {
    $this->actingAs($this->officer)
        ->patch(route('monitoring.tasks.status', $this->task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    Notification::assertSentTo($this->lead, TaskStatusChanged::class, function (TaskStatusChanged $n) {
        $mail = $n->toMail($this->lead);
        $body = collect($mail->introLines)->implode("\n");

        return $mail->subject === "Status changed: {$this->task->title}"
            && str_contains($body, 'Pending → **Completed**');
    });
});
