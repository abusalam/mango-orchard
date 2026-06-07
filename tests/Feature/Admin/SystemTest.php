<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

// ============== Page gating ==============

it('blocks a regular user from the system page', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.system.index'))
        ->assertForbidden();
});

it('lets an admin with settings.manage open the system page', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-testid="system-schedule"', escape: false)
        ->assertSee('data-testid="system-queue-stats"', escape: false)
        ->assertSee('data-testid="system-failed-jobs"', escape: false);
});

// ============== Schedule rendering ==============

it('lists the daily monitoring deadline reminder job in the schedule', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('monitoring:dispatch-deadline-reminders');
});

// ============== Queue stats ==============

it('renders queue stat cards', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-testid="queue-pending"', escape: false)
        ->assertSee('data-testid="queue-reserved"', escape: false)
        ->assertSee('data-testid="queue-delayed"', escape: false)
        ->assertSee('data-testid="queue-failed"', escape: false);
});

// ============== Worker heartbeat ==============

it('shows "Worker running" when the heartbeat is fresh', function () {
    $admin = User::factory()->superuser()->create();
    Cache::put('queue:worker:heartbeat', now()->timestamp);

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-testid="worker-status-running"', escape: false)
        ->assertSee('Worker running');
});

it('shows "Worker not detected" when the heartbeat is stale', function () {
    $admin = User::factory()->superuser()->create();
    Cache::put('queue:worker:heartbeat', now()->subSeconds(30)->timestamp);

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-testid="worker-status-stopped"', escape: false)
        ->assertSee('last heartbeat');
});

it('shows "Worker not detected · no heartbeat seen" when never started', function () {
    $admin = User::factory()->superuser()->create();
    Cache::forget('queue:worker:heartbeat');

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('data-testid="worker-status-stopped"', escape: false)
        ->assertSee('no heartbeat seen');
});

// ============== Failed job actions ==============

it('lists rows from the failed_jobs table', function () {
    $admin = User::factory()->superuser()->create();
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\SeedTest']),
        'exception' => "RuntimeException: kaboom\n#0 fake_trace.php",
        'failed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.system.index'))
        ->assertOk()
        ->assertSee('SeedTest')
        ->assertSee('kaboom');
});

it('forgets a single failed job', function () {
    $admin = User::factory()->superuser()->create();
    $uuid = (string) Str::uuid();
    DB::table('failed_jobs')->insert([
        'uuid' => $uuid,
        'connection' => 'database',
        'queue' => 'default',
        'payload' => json_encode(['displayName' => 'App\\Jobs\\X']),
        'exception' => 'boom',
        'failed_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.system.failed.forget', $uuid))
        ->assertRedirect();

    expect(DB::table('failed_jobs')->where('uuid', $uuid)->exists())->toBeFalse();
});

it('flushes all failed jobs', function () {
    $admin = User::factory()->superuser()->create();
    foreach (range(1, 3) as $i) {
        DB::table('failed_jobs')->insert([
            'uuid' => (string) Str::uuid(),
            'connection' => 'database',
            'queue' => 'default',
            'payload' => json_encode(['displayName' => "App\\Jobs\\J{$i}"]),
            'exception' => 'boom',
            'failed_at' => now(),
        ]);
    }

    $this->actingAs($admin)
        ->post(route('admin.system.failed.flush'))
        ->assertRedirect();

    expect(DB::table('failed_jobs')->count())->toBe(0);
});
