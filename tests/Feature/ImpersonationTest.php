<?php

declare(strict_types=1);

use App\Models\TelemetryEvent;
use App\Models\User;
use App\Permissions;
use App\Roles;
use App\Services\Impersonation;
use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

// ============== Permission + role setup ==============

it('seeds the impersonator role with the users.impersonate permission', function () {
    $role = Role::findByName(Roles::IMPERSONATOR);
    expect($role->hasPermissionTo(Permissions::USERS_IMPERSONATE))->toBeTrue();
});

it('grants the superuser the users.impersonate permission via seeded role assignment', function () {
    $superuser = User::factory()->superuser()->create();
    expect($superuser->can(Permissions::USERS_IMPERSONATE))->toBeTrue();
});

it('does not grant impersonate to plain users', function () {
    $user = User::factory()->create();
    expect($user->can(Permissions::USERS_IMPERSONATE))->toBeFalse();
});

// ============== Self-application exclusion ==============

it('does not list the impersonator role on the request-role profile section', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');

    $impersonatorRoleId = Role::findByName(Roles::IMPERSONATOR)->id;
    $response->assertOk()
        ->assertDontSee('value="'.$impersonatorRoleId.'"', escape: false);
});

it('rejects role applications for the impersonator role', function () {
    $user = User::factory()->create();
    $impersonator = Role::findByName(Roles::IMPERSONATOR);

    $this->actingAs($user)
        ->from('/profile')
        ->post('/role-applications', ['role_id' => $impersonator->id])
        ->assertSessionHasErrors('role_id');
});

// ============== /admin/impersonate access control ==============

it('lets users with users.impersonate reach the admin impersonate page', function () {
    $impersonator = User::factory()->impersonator()->create();

    $this->actingAs($impersonator)
        ->get('/admin/impersonate')
        ->assertOk()
        ->assertSee('Impersonate');
});

it('blocks users without users.impersonate from the admin impersonate page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get('/admin/impersonate')->assertForbidden();
});

// ============== User impersonation (start/stop) ==============

it('lets an impersonator start impersonating a regular user', function () {
    $impersonator = User::factory()->impersonator()->create(['name' => 'Mr Impersonator']);
    $target = User::factory()->create(['name' => 'Target Person']);

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/users/{$target->id}")
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($target->id);
    expect(session(Impersonation::SESSION_KEY))->toBe($impersonator->id);
});

it('stops impersonating and restores the original user', function () {
    $impersonator = User::factory()->impersonator()->create();
    $target = User::factory()->create();

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/users/{$target->id}")
        ->assertRedirect('/dashboard');

    $this->post('/impersonate/stop')->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($impersonator->id);
    expect(session(Impersonation::SESSION_KEY))->toBeNull();
});

it('forbids impersonating yourself', function () {
    $impersonator = User::factory()->impersonator()->create();

    $this->actingAs($impersonator)
        ->from('/admin/impersonate')
        ->post("/admin/impersonate/users/{$impersonator->id}")
        ->assertSessionHasErrors('impersonate');

    expect(auth()->id())->toBe($impersonator->id);
    expect(session(Impersonation::SESSION_KEY))->toBeNull();
});

it('forbids a non-superuser impersonator from impersonating a superuser', function () {
    $impersonator = User::factory()->impersonator()->create();
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($impersonator)
        ->from('/admin/impersonate')
        ->post("/admin/impersonate/users/{$superuser->id}")
        ->assertSessionHasErrors('impersonate');

    expect(auth()->id())->toBe($impersonator->id);
});

it('allows a superuser to impersonate another superuser', function () {
    $actor = User::factory()->superuser()->create();
    $other = User::factory()->superuser()->create();

    $this->actingAs($actor)
        ->post("/admin/impersonate/users/{$other->id}")
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($other->id);
});

// ============== Role impersonation ==============

it('impersonates the first user holding a given role', function () {
    $impersonator = User::factory()->impersonator()->create();
    $grower = User::factory()->grower()->create(['name' => 'First Grower']);
    User::factory()->grower()->create(['name' => 'Second Grower']);

    $growerRole = Role::findByName(Roles::GROWER);

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/roles/{$growerRole->id}")
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($grower->id);
});

it('refuses to impersonate-by-role when no other user holds that role', function () {
    $impersonator = User::factory()->impersonator()->create();
    $editorRole = Role::findByName(Roles::EDITOR);
    // No editors exist.

    $this->actingAs($impersonator)
        ->from('/admin/impersonate')
        ->post("/admin/impersonate/roles/{$editorRole->id}")
        ->assertSessionHasErrors('impersonate');
});

it('refuses to impersonate-by-role for the impersonator role itself', function () {
    $impersonator = User::factory()->impersonator()->create();
    User::factory()->impersonator()->create(); // another impersonator exists
    $impRole = Role::findByName(Roles::IMPERSONATOR);

    $this->actingAs($impersonator)
        ->from('/admin/impersonate')
        ->post("/admin/impersonate/roles/{$impRole->id}")
        ->assertSessionHasErrors('impersonate');

    expect(auth()->id())->toBe($impersonator->id);
});

// ============== Banner + telemetry ==============

it('renders the impersonation banner on pages once impersonation is active', function () {
    $impersonator = User::factory()->impersonator()->create(['name' => 'Banner Boss']);
    $target = User::factory()->create(['name' => 'Banner Target']);

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/users/{$target->id}");

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('data-testid="impersonation-banner"', escape: false)
        ->assertSee('Banner Target')
        ->assertSee('Banner Boss');
});

it('does not render the banner when not impersonating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('data-testid="impersonation-banner"', escape: false);
});

it('records telemetry on impersonation start and stop', function () {
    $impersonator = User::factory()->impersonator()->create();
    $target = User::factory()->create();

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/users/{$target->id}");

    $startEvent = TelemetryEvent::where('event', Telemetry::IMPERSONATION_STARTED)->latest('id')->first();
    expect($startEvent)->not->toBeNull();
    expect($startEvent->user_id)->toBe($impersonator->id); // recorded against the actor
    expect($startEvent->context['target_id'])->toBe($target->id);

    $this->post('/impersonate/stop');

    $stopEvent = TelemetryEvent::where('event', Telemetry::IMPERSONATION_STOPPED)->latest('id')->first();
    expect($stopEvent)->not->toBeNull();
    expect($stopEvent->user_id)->toBe($impersonator->id);
});

// ============== Stop endpoint edge cases ==============

it('stop endpoint is a harmless no-op when not impersonating', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/impersonate/stop')
        ->assertRedirect('/dashboard');

    expect(auth()->id())->toBe($user->id);
});

it('stop endpoint is reachable while impersonating even if the target lacks any admin permission', function () {
    $impersonator = User::factory()->impersonator()->create();
    $target = User::factory()->create(); // plain user, no perms

    $this->actingAs($impersonator)
        ->post("/admin/impersonate/users/{$target->id}");

    expect(auth()->id())->toBe($target->id);
    expect(auth()->user()->can(Permissions::USERS_IMPERSONATE))->toBeFalse();

    $this->post('/impersonate/stop')->assertRedirect('/dashboard');
    expect(auth()->id())->toBe($impersonator->id);
});

// ============== Admin home redirect ==============

it('admin.home redirects an impersonate-only user to the impersonate page', function () {
    $impersonator = User::factory()->impersonator()->create();

    $this->actingAs($impersonator)
        ->get('/admin')
        ->assertRedirect('/admin/impersonate');
});

// ============== UI surface ==============

it('shows the impersonate sidebar link to impersonators', function () {
    $impersonator = User::factory()->impersonator()->create();

    $this->actingAs($impersonator)
        ->get('/admin/impersonate')
        ->assertOk()
        ->assertSee(route('admin.impersonate.index'));
});

it('renders an Impersonate button next to other users on /admin/users for superusers', function () {
    $superuser = User::factory()->superuser()->create(['name' => 'Boss']);
    User::factory()->create(['name' => 'Pickme']);

    $this->actingAs($superuser)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('data-testid="impersonate-button"', escape: false);
});
