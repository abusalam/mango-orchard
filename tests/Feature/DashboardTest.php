<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\Event;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\RoleApplication;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

beforeEach(fn () => app(Settings::class)->forget());

// ============== Base rendering ==============

it('renders the dashboard for a plain authenticated user', function () {
    $user = User::factory()->create(['name' => 'Plain Pat']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Welcome back, Plain Pat.')
        ->assertSee('Your role')
        ->assertSee('no roles yet');
});

it('redirects guests away from the dashboard', function () {
    $this->get('/dashboard')->assertRedirect('/login');
});

// ============== Personal section ==============

it('shows the listings card for a grower with status breakdown', function () {
    $user = User::factory()->grower()->create();
    Listing::factory()->count(2)->create(['user_id' => $user->id, 'status' => Listing::STATUS_PUBLISHED]);
    Listing::factory()->draft()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your listings')
        ->assertSee('2 published', escape: false)
        ->assertSee('1 draft', escape: false);
});

it('shows a "request a role" nudge for plain users with no roles or pending application', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Want to do more?');
});

it('omits the role-nudge card for users who have a pending application', function () {
    $user = User::factory()->create();
    RoleApplication::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Want to do more?')
        ->assertSee('pending request');
});

// ============== Attention section ==============

it('surfaces pending role applications to admins with USERS_MANAGE', function () {
    $admin = User::factory()->superuser()->create();
    User::factory()->count(3)->create()->each(function ($u) {
        RoleApplication::factory()->create(['user_id' => $u->id]);
    });

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk()->assertSee('dashboard-attention', escape: false);

    // The count lives inside a dedicated <strong> with a stable test id.
    expect(preg_match(
        '/data-testid="attention-pending-role-applications-count">(\d+)</',
        $response->getContent(),
        $m,
    ))->toBe(1);
    expect((int) $m[1])->toBe(3);
});

it('does not surface pending role applications to users without USERS_MANAGE', function () {
    $grower = User::factory()->grower()->create();
    User::factory()->count(2)->create()->each(function ($u) {
        RoleApplication::factory()->create(['user_id' => $u->id]);
    });

    $this->actingAs($grower)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('waiting for review');
});

it('warns about failed-login spikes for telemetry viewers', function () {
    $admin = User::factory()->superuser()->create();
    foreach (range(1, 6) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::AUTH_LOGIN_FAILED,
            'occurred_at' => now()->subMinutes(30),
        ]);
    }

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('failed logins in the last 24 hours');
});

it('does not warn about failed-login spikes below the 5 threshold', function () {
    $admin = User::factory()->superuser()->create();
    foreach (range(1, 3) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::AUTH_LOGIN_FAILED,
            'occurred_at' => now()->subMinutes(30),
        ]);
    }

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('failed logins in the last 24 hours');
});

it('warns settings managers when dev-only flags are still on', function () {
    $admin = User::factory()->superuser()->create();
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Dev-only setting')
        ->assertSee('Form autofill');
});

it('shows the user their own most recent role-application decision', function () {
    $user = User::factory()->create();
    $role = Role::findByName(Roles::CURATOR);
    RoleApplication::factory()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => RoleApplication::STATUS_APPROVED,
        'reviewed_at' => now()->subHours(2),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Your application for the')
        ->assertSee(Roles::CURATOR)
        ->assertSee('approved');
});

it('does not show stale role-application decisions (older than 14 days)', function () {
    $user = User::factory()->create();
    RoleApplication::factory()->create([
        'user_id' => $user->id,
        'status' => RoleApplication::STATUS_APPROVED,
        'reviewed_at' => now()->subDays(30),
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Your application for the');
});

// ============== Orchard stats section ==============

it('shows orchard-wide counters to users with admin perms', function () {
    User::factory()->count(4)->create();
    MangoVariety::factory()->count(3)->create();
    Listing::factory()->count(2)->create(['status' => Listing::STATUS_PUBLISHED]);
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('At a glance')
        ->assertSee('Listings live')
        ->assertSee('Varieties')
        ->assertSee('Upcoming events');
});

it('hides the orchard counters section from users without any admin perm', function () {
    $grower = User::factory()->grower()->create();

    $this->actingAs($grower)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('At a glance');
});

it('renders the events upcoming counter (excludes drafts + past)', function () {
    $admin = User::factory()->superuser()->create();
    Event::factory()->create([
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->addDays(3),
    ]);
    Event::factory()->create([
        'status' => Event::STATUS_PUBLISHED,
        'start_at' => now()->subDays(3),
    ]);
    Event::factory()->create([
        'status' => Event::STATUS_DRAFT,
        'start_at' => now()->addDays(10),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk();
    expect(preg_match(
        '/data-testid="orchard-events-upcoming"[^>]*>(\d+)</',
        $response->getContent(),
        $m,
    ))->toBe(1);
    expect((int) $m[1])->toBe(1);
});

// ============== Recent activity feed ==============

it('shows the recent telemetry feed to telemetry viewers', function () {
    $admin = User::factory()->superuser()->create();
    TelemetryEvent::create([
        'event' => 'test.unique-marker-event',
        'occurred_at' => now()->subMinutes(5),
    ]);

    $this->actingAs($admin)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Latest activity')
        ->assertSee('test.unique-marker-event');
});

it('does not show the activity feed to users without telemetry.view', function () {
    $grower = User::factory()->grower()->create();
    TelemetryEvent::create([
        'event' => 'test.should-not-appear-event',
        'occurred_at' => now(),
    ]);

    $this->actingAs($grower)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Latest activity')
        ->assertDontSee('test.should-not-appear-event');
});

// ============== Telemetry insights (charts) ==============

it('shows the telemetry insights section to telemetry viewers', function () {
    $admin = User::factory()->superuser()->create();
    foreach (range(1, 5) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::AUTH_LOGIN_SUCCEEDED,
            'occurred_at' => now()->subHours(2),
        ]);
    }
    foreach (range(1, 2) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::AUTH_LOGIN_FAILED,
            'occurred_at' => now()->subHours(2),
        ]);
    }

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk()
        ->assertSee('Telemetry insights')
        ->assertSee('Events / day')
        ->assertSee('Auth health')
        ->assertSee('Top events')
        ->assertSee('dashboard-telemetry-insights', escape: false)
        ->assertSee('telemetry-sparkline', escape: false)
        ->assertSee('telemetry-auth-health', escape: false);

    // Auth success rate should be 5 / (5 + 2) ≈ 71%.
    expect(preg_match(
        '/data-testid="telemetry-auth-success-pct">(\d+)%</',
        $response->getContent(),
        $m,
    ))->toBe(1);
    expect((int) $m[1])->toBe(71);
});

it('hides the telemetry insights section from users without TELEMETRY_VIEW', function () {
    $grower = User::factory()->grower()->create();
    TelemetryEvent::create([
        'event' => Telemetry::AUTH_LOGIN_SUCCEEDED,
        'occurred_at' => now(),
    ]);

    $this->actingAs($grower)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('Telemetry insights')
        ->assertDontSee('Auth health')
        ->assertDontSee('dashboard-telemetry-insights', escape: false);
});

it('lists the top telemetry events with their counts in the bar chart', function () {
    $admin = User::factory()->superuser()->create();
    foreach (range(1, 7) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::LISTING_CREATED,
            'occurred_at' => now()->subHours(2),
        ]);
    }
    foreach (range(1, 3) as $_) {
        TelemetryEvent::create([
            'event' => Telemetry::VARIETY_CREATED,
            'occurred_at' => now()->subHours(2),
        ]);
    }

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk()
        ->assertSee(Telemetry::LISTING_CREATED)
        ->assertSee(Telemetry::VARIETY_CREATED)
        ->assertSee('telemetry-top-events', escape: false);
});

it('renders the sparkline with 14 daily data points (including zero-count days)', function () {
    $admin = User::factory()->superuser()->create();
    // Only one event, recorded today — gaps should still render zero-count days.
    TelemetryEvent::create([
        'event' => 'test.lone-event',
        'occurred_at' => now(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk();

    // The sparkline polyline should have 14 comma-separated x,y points.
    expect(preg_match(
        '/<polyline points="([^"]+)" fill="none"/',
        $response->getContent(),
        $m,
    ))->toBe(1);
    expect(substr_count($m[1], ','))->toBe(14);
});

it('counts distinct active users in the last 7 days', function () {
    $admin = User::factory()->superuser()->create();
    $other = User::factory()->create();
    // 3 events for the admin + 2 for $other — should count as 2 distinct users.
    foreach (range(1, 3) as $_) {
        TelemetryEvent::create([
            'event' => 'test.ev',
            'user_id' => $admin->id,
            'occurred_at' => now()->subHour(),
        ]);
    }
    foreach (range(1, 2) as $_) {
        TelemetryEvent::create([
            'event' => 'test.ev',
            'user_id' => $other->id,
            'occurred_at' => now()->subHour(),
        ]);
    }
    // Plus a guest event that should NOT count.
    TelemetryEvent::create([
        'event' => 'test.ev',
        'user_id' => null,
        'occurred_at' => now()->subHour(),
    ]);

    $response = $this->actingAs($admin)->get('/dashboard');
    $response->assertOk();

    expect(preg_match(
        '/data-testid="telemetry-active-users-7d">(\d+)</',
        $response->getContent(),
        $m,
    ))->toBe(1);
    expect((int) $m[1])->toBe(2);
});

// ============== Tightened per-permission orchard gating ==============

it('shows only the orchard tiles each permission unlocks', function () {
    // A user with only VARIETIES_MANAGE sees the Varieties tile and nothing else.
    $role = Role::findOrCreate('test-varieties-only', 'web');
    $role->syncPermissions([\App\Permissions::VARIETIES_MANAGE]);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/dashboard');
    $response->assertOk()
        ->assertSee('At a glance')
        ->assertSee('Varieties')
        ->assertDontSee('Users')
        ->assertDontSee('Listings live')
        ->assertDontSee('Upcoming events')
        ->assertDontSee('Events / 24h');
});

it('hides the orchard section entirely when no admin permission is held', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('At a glance');
});
