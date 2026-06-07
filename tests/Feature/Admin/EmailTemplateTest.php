<?php

declare(strict_types=1);

use App\Models\EmailTemplate;
use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskStatusChanged;
use Database\Seeders\EmailTemplateSeeder;
use Illuminate\Support\Facades\Notification;

beforeEach(function (): void {
    (new EmailTemplateSeeder())->run();
});

// ============== Renderer ==============

it('substitutes placeholders into the subject + body', function () {
    $tpl = EmailTemplate::forKey('task.status_changed');
    expect($tpl)->not->toBeNull();

    $msg = \App\Mail\EmailTemplateRenderer::render('task.status_changed', [
        'actor_name' => 'Anjali Sen',
        'task_title' => 'Inspect site',
        'scheme_name' => 'DWP',
        'old_status' => 'Pending',
        'new_status' => 'Completed',
        'deadline' => '12 Jun 2026',
    ], 'Rahim Bose');

    expect($msg->subject)->toBe('Status changed: Inspect site');
    $body = implode("\n", $msg->introLines);
    expect($body)
        ->toContain('Anjali Sen changed the status')
        ->toContain('**Task:** Inspect site')
        ->toContain('Pending → **Completed**');
});

it('leaves unknown placeholders literal so typos are visible', function () {
    EmailTemplate::create([
        'key' => 'unit.unknown',
        'name' => 'Test',
        'subject' => 'Hi {does_not_exist}',
        'body' => 'Body with {missing_placeholder}.',
    ]);

    $msg = \App\Mail\EmailTemplateRenderer::render('unit.unknown', [], 'Test');

    expect($msg->subject)->toBe('Hi {does_not_exist}');
    expect(implode("\n", $msg->introLines))->toContain('{missing_placeholder}');
});

it('returns null when the template row is missing', function () {
    $msg = \App\Mail\EmailTemplateRenderer::render('does.not.exist', [], 'Test');
    expect($msg)->toBeNull();
});

it('caches templates and invalidates on save', function () {
    EmailTemplate::where('key', 'task.status_changed')->first()
        ->update(['subject' => 'Edited subject {task_title}']);

    $msg = \App\Mail\EmailTemplateRenderer::render('task.status_changed', [
        'task_title' => 'Re-rendered',
    ] + array_fill_keys(['actor_name', 'scheme_name', 'old_status', 'new_status', 'deadline'], ''), 'Test');

    expect($msg->subject)->toBe('Edited subject Re-rendered');
});

// ============== Notification integration ==============

it('uses the edited template when TaskStatusChanged renders', function () {
    Notification::fake();

    EmailTemplate::where('key', 'task.status_changed')->first()
        ->update([
            'subject' => 'CUSTOM: {task_title}',
            'body' => "Top line.\n\nStatus is now {new_status}.",
        ]);

    $lead = User::factory()->monitor()->create(['name' => 'Lead']);
    $officer = User::factory()->monitor()->create(['name' => 'Officer']);
    monitorHierarchy([[$lead, null], [$officer, $lead]]);
    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $officer->id,
        'created_by' => $lead->id,
        'status' => Task::STATUS_PENDING,
    ]);

    $this->actingAs($officer)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    Notification::assertSentTo($lead, TaskStatusChanged::class, function (TaskStatusChanged $n) use ($lead) {
        $mail = $n->toMail($lead);

        return $mail->subject === "CUSTOM: {$n->task->title}"
            && str_contains(implode("\n", $mail->introLines), 'Status is now Completed.');
    });
});

it('falls back to hard-coded copy if the template row is missing', function () {
    Notification::fake();
    EmailTemplate::where('key', 'task.status_changed')->delete();

    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    monitorHierarchy([[$lead, null], [$officer, $lead]]);
    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create([
        'scheme_id' => $scheme->id,
        'assigned_to' => $officer->id,
        'created_by' => $lead->id,
        'status' => Task::STATUS_PENDING,
    ]);

    $this->actingAs($officer)
        ->patch(route('monitoring.tasks.status', $task), ['status' => Task::STATUS_COMPLETED])
        ->assertRedirect();

    // Email still sends — fallback in TaskStatusChanged uses the same subject pattern.
    Notification::assertSentTo($lead, TaskStatusChanged::class, function (TaskStatusChanged $n) use ($lead) {
        return str_contains($n->toMail($lead)->subject, 'Status changed:');
    });
});

// ============== Admin UI ==============

it('blocks a regular user from the email-templates index', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.email-templates.index'))
        ->assertForbidden();
});

it('lists templates for an admin with monitoring.manage', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.email-templates.index'))
        ->assertOk()
        ->assertSee('Task — status changed')
        ->assertSee('Task — fields updated')
        ->assertSee('task.deadline_reminder.t-7');
});

it('shows placeholder docs on the edit page', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $this->actingAs($admin)
        ->get(route('admin.email-templates.edit', $tpl))
        ->assertOk()
        ->assertSee('data-testid="placeholder-docs"', escape: false)
        ->assertSee('{task_title}')
        ->assertSee('{old_status}');
});

it('updates a template via the edit form', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $this->actingAs($admin)
        ->put(route('admin.email-templates.update', $tpl), [
            'subject' => 'NEW SUBJECT {task_title}',
            'body' => 'NEW BODY',
        ])
        ->assertRedirect(route('admin.email-templates.index'));

    expect($tpl->fresh()->subject)->toBe('NEW SUBJECT {task_title}');
    expect($tpl->fresh()->body)->toBe('NEW BODY');
});

it('rejects an empty subject', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $this->actingAs($admin)
        ->put(route('admin.email-templates.update', $tpl), [
            'subject' => '',
            'body' => 'Body',
        ])
        ->assertSessionHasErrors('subject');
});

// ============== Preview ==============

it('renders the saved template via GET preview with sample data', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $response = $this->actingAs($admin)
        ->get(route('admin.email-templates.preview', $tpl))
        ->assertOk();

    $body = $response->getContent();
    expect($body)
        ->toContain('Preview · sample data')
        ->toContain('task.status_changed')
        ->toContain('Inspect block water tank') // sample task_title
        ->toContain('Anjali Sen')                // sample actor_name
        ->toContain('Drinking Water Programme'); // sample scheme_name
});

it('renders unsaved form values via POST preview', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $response = $this->actingAs($admin)
        ->post(route('admin.email-templates.preview', $tpl), [
            'subject' => 'UNSAVED SUBJECT {task_title}',
            'body' => "UNSAVED BODY.\n\nStatus is now {new_status}.",
        ])
        ->assertOk();

    $body = $response->getContent();
    expect($body)
        ->toContain('UNSAVED SUBJECT Inspect block water tank — Mothabari')
        ->toContain('UNSAVED BODY.')
        ->toContain('Status is now In progress.'); // sample new_status
});

it('includes the structural extras (action button + attachments) in the preview', function () {
    $admin = User::factory()->superuser()->create();
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();

    $body = $this->actingAs($admin)
        ->get(route('admin.email-templates.preview', $tpl))
        ->assertOk()
        ->getContent();

    expect($body)
        ->toContain('Open task')                  // action button
        ->toContain('inspection-report.pdf')      // sample attachment
        ->toContain('site-photo.jpg');
});

it('uses different sample data per deadline-reminder kind', function () {
    $admin = User::factory()->superuser()->create();
    $t7 = EmailTemplate::where('key', 'task.deadline_reminder.t-7')->first();
    $t1 = EmailTemplate::where('key', 'task.deadline_reminder.t-1')->first();

    $body7 = $this->actingAs($admin)->get(route('admin.email-templates.preview', $t7))->getContent();
    $body1 = $this->actingAs($admin)->get(route('admin.email-templates.preview', $t1))->getContent();

    expect($body7)->toContain('due in 7 days');
    expect($body1)->toContain('due tomorrow');
});

it('blocks regular users from preview', function () {
    $tpl = EmailTemplate::where('key', 'task.status_changed')->first();
    $this->actingAs(User::factory()->create())
        ->get(route('admin.email-templates.preview', $tpl))
        ->assertForbidden();
});

// ============== Seeder ==============

it('is idempotent when re-run', function () {
    $before = EmailTemplate::count();

    (new EmailTemplateSeeder())->run();
    (new EmailTemplateSeeder())->run();

    expect(EmailTemplate::count())->toBe($before);
});

it('preserves admin edits on re-seed', function () {
    EmailTemplate::where('key', 'task.status_changed')->first()
        ->update(['subject' => 'Admin-edited subject']);

    (new EmailTemplateSeeder())->run();

    expect(EmailTemplate::where('key', 'task.status_changed')->value('subject'))
        ->toBe('Admin-edited subject');
});
