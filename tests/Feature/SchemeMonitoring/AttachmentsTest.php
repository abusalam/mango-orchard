<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Attachment;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

beforeEach(function (): void {
    Storage::fake('public');

    $this->owner = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $this->owner->id, 'parent_user_id' => null]);
    $this->scheme = Scheme::factory()->create(['owner_id' => $this->owner->id]);
    $this->task = Task::factory()->create(['scheme_id' => $this->scheme->id, 'assigned_to' => $this->owner->id]);
});

// ============== Upload — scheme ==============

it('lets the scheme owner attach a file to a scheme', function () {
    $file = UploadedFile::fake()->create('report.pdf', 200, 'application/pdf');

    $this->actingAs($this->owner)
        ->post(route('monitoring.schemes.attachments.store', $this->scheme), ['file' => $file])
        ->assertRedirect();

    $attachment = $this->scheme->attachments()->latest('id')->first();
    expect($attachment)->not->toBeNull()
        ->original_name->toBe('report.pdf')
        ->uploaded_by->toBe($this->owner->id)
        ->mime_type->toBe('application/pdf');
    Storage::disk('public')->assertExists($attachment->path);
});

it('rejects a file larger than the 10 MB ceiling', function () {
    $file = UploadedFile::fake()->create('huge.pdf', 11 * 1024); // 11 MB

    $this->actingAs($this->owner)
        ->post(route('monitoring.schemes.attachments.store', $this->scheme), ['file' => $file])
        ->assertSessionHasErrors('file');

    expect($this->scheme->attachments()->count())->toBe(0);
});

it('forbids attaching to a scheme you do not own', function () {
    $other = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $other->id, 'parent_user_id' => null]);
    $file = UploadedFile::fake()->create('snoop.pdf', 100, 'application/pdf');

    $this->actingAs($other)
        ->post(route('monitoring.schemes.attachments.store', $this->scheme), ['file' => $file])
        ->assertForbidden();
});

// ============== Upload — task ==============

it('lets a viewer within the subtree attach a file to a task', function () {
    $file = UploadedFile::fake()->create('progress-photo.jpg', 100, 'image/jpeg');

    $this->actingAs($this->owner)
        ->post(route('monitoring.tasks.attachments.store', $this->task), ['file' => $file])
        ->assertRedirect();

    expect($this->task->attachments()->count())->toBe(1);
    $stored = $this->task->attachments()->first();
    Storage::disk('public')->assertExists($stored->path);
});

it('forbids attaching to a task outside the viewer subtree', function () {
    $stranger = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $stranger->id, 'parent_user_id' => null]);
    $file = UploadedFile::fake()->create('intrude.pdf', 100, 'application/pdf');

    $this->actingAs($stranger)
        ->post(route('monitoring.tasks.attachments.store', $this->task), ['file' => $file])
        ->assertForbidden();
});

// ============== Delete ==============

it('lets the uploader delete their own attachment', function () {
    $file = UploadedFile::fake()->create('mine.pdf', 100, 'application/pdf');
    $this->actingAs($this->owner)
        ->post(route('monitoring.tasks.attachments.store', $this->task), ['file' => $file]);
    $attachment = $this->task->attachments()->firstOrFail();
    $path = $attachment->path;

    $this->actingAs($this->owner)
        ->delete(route('monitoring.attachments.destroy', $attachment))
        ->assertRedirect();

    expect(Attachment::find($attachment->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('lets a supervisor delete an attachment uploaded by a subordinate', function () {
    $lead = User::factory()->monitor()->create();
    $officer = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $lead->id, 'parent_user_id' => null]);
    MonitorProfile::create(['user_id' => $officer->id, 'parent_user_id' => $lead->id]);

    $scheme = Scheme::factory()->create(['owner_id' => $lead->id]);
    $task = Task::factory()->create(['scheme_id' => $scheme->id, 'assigned_to' => $officer->id]);

    $file = UploadedFile::fake()->create('officer-upload.pdf', 100, 'application/pdf');
    $this->actingAs($officer)
        ->post(route('monitoring.tasks.attachments.store', $task), ['file' => $file]);
    $attachment = $task->attachments()->firstOrFail();

    $this->actingAs($lead)
        ->delete(route('monitoring.attachments.destroy', $attachment))
        ->assertRedirect();

    expect(Attachment::find($attachment->id))->toBeNull();
});

it('forbids a stranger from deleting someone else\'s attachment', function () {
    $stranger = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $stranger->id, 'parent_user_id' => null]);

    $file = UploadedFile::fake()->create('mine.pdf', 100, 'application/pdf');
    $this->actingAs($this->owner)
        ->post(route('monitoring.tasks.attachments.store', $this->task), ['file' => $file]);
    $attachment = $this->task->attachments()->firstOrFail();

    $this->actingAs($stranger)
        ->delete(route('monitoring.attachments.destroy', $attachment))
        ->assertForbidden();

    expect(Attachment::find($attachment->id))->not->toBeNull();
});

// ============== Cascade on parent delete ==============

it('removes attachments + their blobs when the parent task is deleted', function () {
    $file = UploadedFile::fake()->create('child.pdf', 100, 'application/pdf');
    $this->actingAs($this->owner)
        ->post(route('monitoring.tasks.attachments.store', $this->task), ['file' => $file]);
    $attachment = $this->task->attachments()->firstOrFail();
    $path = $attachment->path;

    $this->task->delete();

    expect(Attachment::find($attachment->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('removes attachments + their blobs when the parent scheme is deleted', function () {
    $file = UploadedFile::fake()->create('child.pdf', 100, 'application/pdf');
    $this->actingAs($this->owner)
        ->post(route('monitoring.schemes.attachments.store', $this->scheme), ['file' => $file]);
    $attachment = $this->scheme->attachments()->firstOrFail();
    $path = $attachment->path;

    $this->scheme->delete();

    expect(Attachment::find($attachment->id))->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

// ============== UI ==============

it('renders the attachments panel on the task edit page', function () {
    Attachment::factory()->create([
        'attachable_type' => $this->task->getMorphClass(),
        'attachable_id' => $this->task->id,
        'uploaded_by' => $this->owner->id,
        'original_name' => 'visible-attachment.pdf',
    ]);

    $this->actingAs($this->owner)
        ->get(route('monitoring.tasks.edit', $this->task))
        ->assertOk()
        ->assertSee('data-testid="attachments-panel"', escape: false)
        ->assertSee('visible-attachment.pdf');
});

it('renders the attachments panel on the scheme edit page', function () {
    Attachment::factory()->create([
        'attachable_type' => $this->scheme->getMorphClass(),
        'attachable_id' => $this->scheme->id,
        'uploaded_by' => $this->owner->id,
        'original_name' => 'scheme-doc.pdf',
    ]);

    $this->actingAs($this->owner)
        ->get(route('monitoring.schemes.edit', $this->scheme))
        ->assertOk()
        ->assertSee('data-testid="attachments-panel"', escape: false)
        ->assertSee('scheme-doc.pdf');
});

it('shows an empty-state message when there are no attachments yet', function () {
    $this->actingAs($this->owner)
        ->get(route('monitoring.schemes.edit', $this->scheme))
        ->assertOk()
        ->assertSee('No files attached yet.');
});
