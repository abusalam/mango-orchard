<?php

declare(strict_types=1);

use App\Captcha\Captcha;
use App\Models\MangoVariety;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Database\Seeders\RolePermissionSeeder;
use Mews\Captcha\Captcha as MewsCaptcha;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    app(Settings::class)->forget();
});

function lastEvent(?string $name = null): ?TelemetryEvent
{
    $q = TelemetryEvent::query();
    if ($name !== null) {
        $q->where('event', $name);
    }

    return $q->orderByDesc('id')->first();
}

it('records auth.registered when a user registers', function () {
    $this->post('/register', [
        'name' => 'Telemetry Tester',
        'email' => 'tele@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ]);

    $event = lastEvent(Telemetry::AUTH_REGISTERED);
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe(User::firstWhere('email', 'tele@example.com')->id)
        ->and($event->subject_type)->toBe((new User)->getMorphClass())
        ->and($event->ip_address)->not->toBeNull();
});

it('records auth.login.succeeded when an existing user logs in', function () {
    $user = User::factory()->create([
        'email' => 'loginme@example.com',
        'password' => bcrypt('correct-pw-1'),
    ]);

    $this->post('/login', [
        'email' => 'loginme@example.com',
        'password' => 'correct-pw-1',
    ])->assertRedirect();

    $event = lastEvent(Telemetry::AUTH_LOGIN_SUCCEEDED);
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe($user->id);
});

it('records auth.login.failed on bad credentials', function () {
    User::factory()->create(['email' => 'real@example.com', 'password' => bcrypt('right-pw-1234')]);

    $this->post('/login', [
        'email' => 'real@example.com',
        'password' => 'wrong-password',
    ])->assertSessionHasErrors();

    $event = lastEvent(Telemetry::AUTH_LOGIN_FAILED);
    expect($event)->not->toBeNull()
        ->and($event->context['email'] ?? null)->toBe('real@example.com');
});

it('records auth.logout when a user logs out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post(route('logout'))->assertRedirect();

    $event = lastEvent(Telemetry::AUTH_LOGOUT);
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe($user->id);
});

it('records auth.captcha.failed on a wrong login captcha', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    User::factory()->create(['email' => 'cap@example.com', 'password' => bcrypt('correct-pw-1')]);
    $result = app(MewsCaptcha::class)->create('default', api: true); // seed session

    $this->post('/login', [
        'email' => 'cap@example.com',
        'password' => 'correct-pw-1',
        Captcha::FIELD => 'wrong',
    ]);

    expect(lastEvent(Telemetry::AUTH_CAPTCHA_FAILED))->not->toBeNull();
});

it('records auth.captcha.failed on a wrong registration captcha', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    app(MewsCaptcha::class)->create('default', api: true);

    $this->post('/register', [
        'name' => 'Bot',
        'email' => 'bot@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
        Captcha::FIELD => 'wrong',
    ]);

    expect(lastEvent(Telemetry::AUTH_CAPTCHA_FAILED))->not->toBeNull();
});

it('records onboarding.profile.saved when profile step is submitted', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)->post(route('onboarding.profile'), [
        'region' => 'Mumbai',
        'expertise' => 'enthusiast',
    ])->assertRedirect();

    $event = lastEvent(Telemetry::ONBOARDING_PROFILE_SAVED);
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe($user->id)
        ->and($event->context['region'])->toBe('Mumbai')
        ->and($event->context['expertise'])->toBe('enthusiast');
});

it('records onboarding.preferences.saved AND onboarding.completed when finishing', function () {
    $user = User::factory()->unonboarded()->create([
        'region' => 'Bangkok',
        'expertise' => 'enthusiast',
    ]);

    $this->actingAs($user)->post(route('onboarding.preferences'), [
        'notify_seasonal' => '1',
    ])->assertRedirect();

    expect(lastEvent(Telemetry::ONBOARDING_PREFERENCES_SAVED))->not->toBeNull()
        ->and(lastEvent(Telemetry::ONBOARDING_COMPLETED))->not->toBeNull();
});

it('records variety.created via the observer', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)->post(route('varieties.store'), [
        'name' => 'Telemetry Mango',
        'origin' => 'Test',
        'season' => 'Apr – Jun',
        'season_start' => 4,
        'season_end' => 6,
        'flavor' => 'Test flavor.',
        'tags' => '',
        'theme' => 'sunrise',
    ])->assertRedirect();

    $event = lastEvent(Telemetry::VARIETY_CREATED);
    expect($event)->not->toBeNull()
        ->and($event->context['name'])->toBe('Telemetry Mango')
        ->and($event->subject_type)->toBe((new MangoVariety)->getMorphClass())
        ->and($event->user_id)->toBe($superuser->id);
});

it('records variety.updated only when fields actually change', function () {
    $variety = MangoVariety::factory()->create();
    $countBefore = TelemetryEvent::where('event', Telemetry::VARIETY_UPDATED)->count();

    $variety->update(['origin' => $variety->origin]); // no-op
    expect(TelemetryEvent::where('event', Telemetry::VARIETY_UPDATED)->count())->toBe($countBefore);

    $variety->update(['origin' => 'A New Origin']);
    $event = lastEvent(Telemetry::VARIETY_UPDATED);
    expect($event)->not->toBeNull()
        ->and($event->context['changed'])->toContain('origin');
});

it('records variety.deleted when a variety is deleted', function () {
    $variety = MangoVariety::factory()->create();
    $name = $variety->name;
    $variety->delete();

    $event = lastEvent(Telemetry::VARIETY_DELETED);
    expect($event)->not->toBeNull()
        ->and($event->context['name'])->toBe($name);
});

it('records role.created/updated/deleted via the spatie observer', function () {
    $role = Role::create(['name' => 'tele-role', 'guard_name' => 'web']);
    expect(lastEvent(Telemetry::ROLE_CREATED))->not->toBeNull();

    $role->update(['name' => 'tele-role-renamed']);
    expect(lastEvent(Telemetry::ROLE_UPDATED))->not->toBeNull();

    $role->delete();
    expect(lastEvent(Telemetry::ROLE_DELETED))->not->toBeNull();
});

it('does NOT record role.created during the RolePermissionSeeder bootstrap', function () {
    $beforeCount = TelemetryEvent::where('event', Telemetry::ROLE_CREATED)->count();

    // beforeEach already ran the seeder once; running again is a no-op.
    $this->seed(RolePermissionSeeder::class);

    expect(TelemetryEvent::where('event', Telemetry::ROLE_CREATED)->count())->toBe($beforeCount);
});

it('records user.roles.updated when an admin changes someone\'s roles', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->create(['email' => 'target@example.com']);

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), ['roles' => [Roles::CURATOR]])
        ->assertRedirect();

    $event = lastEvent(Telemetry::USER_ROLES_UPDATED);
    expect($event)->not->toBeNull()
        ->and($event->user_id)->toBe($admin->id)
        ->and($event->context['target_email'])->toBe('target@example.com')
        ->and($event->context['after'])->toBe([Roles::CURATOR])
        ->and($event->context['before'])->toBe([]);
});

it('does not record user.roles.updated when the submitted roles are unchanged', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->curator()->create();

    $countBefore = TelemetryEvent::where('event', Telemetry::USER_ROLES_UPDATED)->count();

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), ['roles' => [Roles::CURATOR]]);

    expect(TelemetryEvent::where('event', Telemetry::USER_ROLES_UPDATED)->count())->toBe($countBefore);
});

it('records settings.updated only when a value actually changes', function () {
    $admin = User::factory()->superuser()->create();

    // First call: flips captcha from off → on. Should record once.
    $this->actingAs($admin)->put(route('admin.settings.update'), ['captcha_enabled' => '1']);
    $countAfterFirst = TelemetryEvent::where('event', Telemetry::SETTINGS_UPDATED)->count();
    expect($countAfterFirst)->toBe(1);

    // Second call: same payload (captcha still on). Should NOT record again.
    $this->actingAs($admin)->put(route('admin.settings.update'), ['captcha_enabled' => '1']);
    expect(TelemetryEvent::where('event', Telemetry::SETTINGS_UPDATED)->count())->toBe(1);
});

it('captures the changed keys in the settings.updated context', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)->put(route('admin.settings.update'), [
        'captcha_enabled' => '1',
        'captcha_autosolve' => '1',
    ]);

    $event = lastEvent(Telemetry::SETTINGS_UPDATED);
    expect($event)->not->toBeNull()
        ->and($event->context['changed'])->toContain('captcha_enabled')
        ->and($event->context['changed'])->toContain('captcha_autosolve')
        ->and($event->context['values']['captcha_enabled'])->toBeTrue();
});

it('blocks the activity page from users without telemetry.view', function () {
    $this->actingAs(User::factory()->curator()->create())
        ->get(route('admin.telemetry.index'))
        ->assertForbidden();
});

it('lets a superuser open the activity page with recent events', function () {
    $admin = User::factory()->superuser()->create();
    app(Telemetry::class)->record(Telemetry::VARIETY_CREATED, context: ['name' => 'Demo']);
    app(Telemetry::class)->record(Telemetry::AUTH_LOGOUT, userId: $admin->id);

    $this->actingAs($admin)
        ->get(route('admin.telemetry.index'))
        ->assertOk()
        ->assertSee('Activity')
        ->assertSee(Telemetry::VARIETY_CREATED)
        ->assertSee(Telemetry::AUTH_LOGOUT)
        ->assertSee('data-testid="telemetry-row"', false);
});

it('filters events by event name on the activity page', function () {
    $admin = User::factory()->superuser()->create();
    app(Telemetry::class)->record(Telemetry::VARIETY_CREATED, context: ['name' => 'OnlyoneFilterMarker']);
    app(Telemetry::class)->record(Telemetry::AUTH_LOGOUT, context: ['marker' => 'LogoutShouldBeHidden'], userId: $admin->id);

    $response = $this->actingAs($admin)
        ->get(route('admin.telemetry.index', ['event' => Telemetry::VARIETY_CREATED]));

    // The filtered row is rendered; the other row's unique context is not.
    $response->assertSee('OnlyoneFilterMarker')
        ->assertDontSee('LogoutShouldBeHidden')
        ->assertSee('1 event'); // total count
});

it('shows an empty-state when no events match', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.telemetry.index', ['event' => 'nonexistent.event']))
        ->assertSee('data-testid="telemetry-empty"', false)
        ->assertSee('No events recorded yet');
});

it('exposes Activity in the admin sidebar for users with telemetry.view', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->get(route('admin.users.index'))
        ->assertSee('Activity');
});
