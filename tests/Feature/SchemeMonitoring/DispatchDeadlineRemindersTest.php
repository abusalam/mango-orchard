<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskDeadlineReminder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    Notification::fake();
    $this->owner = User::factory()->monitor()->create();
    $this->assignee = User::factory()->monitor()->create();
    $this->scheme = Scheme::factory()->create(['owner_id' => $this->owner->id]);
});

it('sends T-7 reminders for tasks due in exactly 7 days', function () {
    $due7 = Task::factory()->dueIn(7)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);
    Task::factory()->dueIn(6)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);

    $this->artisan('monitoring:dispatch-deadline-reminders')->assertSuccessful();

    Notification::assertSentTo($this->assignee, TaskDeadlineReminder::class,
        fn ($n) => $n->task->is($due7) && $n->kind === 't-7');
    Notification::assertSentTimes(TaskDeadlineReminder::class, 1);
});

it('sends T-3 and T-1 reminders', function () {
    $due3 = Task::factory()->dueIn(3)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);
    $due1 = Task::factory()->dueIn(1)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);

    $this->artisan('monitoring:dispatch-deadline-reminders')->assertSuccessful();

    Notification::assertSentTo($this->assignee, TaskDeadlineReminder::class,
        fn ($n) => $n->task->is($due3) && $n->kind === 't-3');
    Notification::assertSentTo($this->assignee, TaskDeadlineReminder::class,
        fn ($n) => $n->task->is($due1) && $n->kind === 't-1');
    Notification::assertSentTimes(TaskDeadlineReminder::class, 2);
});

it('sends overdue reminders for open tasks past deadline', function () {
    $overdue = Task::factory()->overdueBy(5)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);

    $this->artisan('monitoring:dispatch-deadline-reminders')->assertSuccessful();

    Notification::assertSentTo($this->assignee, TaskDeadlineReminder::class,
        fn ($n) => $n->task->is($overdue) && $n->kind === 'overdue');
    expect($overdue->fresh()->last_overdue_reminder_at)->not->toBeNull();
});

it('does not double-send the overdue reminder on the same day', function () {
    Task::factory()->overdueBy(2)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->assignee->id,
        'last_overdue_reminder_at' => now(),
    ]);

    $this->artisan('monitoring:dispatch-deadline-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('skips completed and cancelled tasks', function () {
    Task::factory()->dueIn(3)->completed()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);
    Task::factory()->dueIn(3)->create([
        'scheme_id' => $this->scheme->id,
        'assigned_to' => $this->assignee->id,
        'status' => Task::STATUS_CANCELLED,
    ]);

    $this->artisan('monitoring:dispatch-deadline-reminders')->assertSuccessful();

    Notification::assertNothingSent();
});

it('respects --dry-run by not actually sending notifications', function () {
    Task::factory()->dueIn(7)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);
    Task::factory()->overdueBy(1)->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->assignee->id]);

    $this->artisan('monitoring:dispatch-deadline-reminders', ['--dry-run' => true])->assertSuccessful();

    Notification::assertNothingSent();
});
