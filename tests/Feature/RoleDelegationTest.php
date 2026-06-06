<?php

declare(strict_types=1);

use App\Models\RoleDelegation;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

// ============== Delegating a role ==============

it('lets a grower delegate the grower role to another user', function () {
    $delegator = User::factory()->grower()->create();
    // Recipient must already be in the Mango Orchard module before a
    // sub-role can be delegated to them.
    $recipient = User::factory()->mangoOrchardMember()->create(['email' => 'newgrower@example.com']);
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeTrue();
    expect(RoleDelegation::active()->where('user_id', $recipient->id)->where('role_id', $role->id)->exists())->toBeTrue();
});

it('refuses to delegate a role the delegator does not hold', function () {
    $delegator = User::factory()->create(); // no grower role
    $recipient = User::factory()->create(['email' => 'a@example.com']);
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('role_id');

    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeFalse();
});

it('refuses to delegate a non-delegatable role (e.g. superuser, impersonator)', function () {
    $delegator = User::factory()->superuser()->create();
    $recipient = User::factory()->create(['email' => 'target@example.com']);
    $superRole = Role::findByName(Roles::SUPERUSER);

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $superRole->id,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('role_id');

    expect($recipient->fresh()->hasRole(Roles::SUPERUSER))->toBeFalse();
});

it('refuses to delegate to yourself', function () {
    $delegator = User::factory()->grower()->create(['email' => 'self@example.com']);
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => $delegator->email,
        ])
        ->assertSessionHasErrors('recipient_email');
});

it('refuses to delegate to a non-existent email', function () {
    $delegator = User::factory()->grower()->create();
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => 'noone-here@example.com',
        ])
        ->assertSessionHasErrors('recipient_email');
});

it('refuses to delegate a role the recipient already holds', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create(['email' => 'alreadygrower@example.com']);
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('recipient_email');
});

it('refuses a duplicate active delegation', function () {
    $delegator = User::factory()->grower()->create();
    $other = User::factory()->grower()->create();
    $recipient = User::factory()->mangoOrchardMember()->create(['email' => 'r@example.com']);
    $role = Role::findByName(Roles::GROWER);

    // First delegator grants successfully.
    $this->actingAs($delegator)->post('/role-delegations', [
        'role_id' => $role->id,
        'recipient_email' => $recipient->email,
    ])->assertSessionHasNoErrors();

    // The recipient now holds the role, so a second delegation from
    // another grower bounces — they already have it.
    $this->actingAs($other)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $role->id,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('recipient_email');
});

it('records telemetry when a role is delegated', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->mangoOrchardMember()->create(['email' => 'tel@example.com']);
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($delegator)->post('/role-delegations', [
        'role_id' => $role->id,
        'recipient_email' => $recipient->email,
    ]);

    expect(TelemetryEvent::where('event', Telemetry::ROLE_DELEGATED)->exists())->toBeTrue();
});

// ============== Revoking a delegation ==============

it('lets the original delegator revoke an active delegation', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($delegator)
        ->delete("/role-delegations/{$delegation->id}")
        ->assertSessionHasNoErrors();

    $delegation->refresh();
    expect($delegation->revoked_at)->not->toBeNull();
    expect($delegation->revoked_by)->toBe($delegator->id);
    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeFalse();
});

it('lets the recipient renounce a delegated role', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($recipient)
        ->delete("/role-delegations/{$delegation->id}")
        ->assertSessionHasNoErrors();

    expect($delegation->fresh()->revoked_by)->toBe($recipient->id);
    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeFalse();
});

it('lets an admin revoke any delegation regardless of being a party to it', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create();
    $admin = User::factory()->superuser()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($admin)
        ->delete("/role-delegations/{$delegation->id}")
        ->assertSessionHasNoErrors();

    expect($delegation->fresh()->revoked_by)->toBe($admin->id);
});

it('forbids a third party (non-admin, non-recipient, non-delegator) from revoking', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create();
    $bystander = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($bystander)
        ->delete("/role-delegations/{$delegation->id}")
        ->assertForbidden();

    expect($delegation->fresh()->revoked_at)->toBeNull();
});

it('cannot revoke a delegation that has already been revoked', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->revoked()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($delegator)
        ->delete("/role-delegations/{$delegation->id}")
        ->assertForbidden();
});

it('allows re-delegating after a previous delegation has been revoked', function () {
    $delegatorA = User::factory()->grower()->create();
    $delegatorB = User::factory()->grower()->create();
    $recipient = User::factory()->mangoOrchardMember()->create(['email' => 'redo@example.com']);
    $role = Role::findByName(Roles::GROWER);

    // First delegation, then revoked.
    $this->actingAs($delegatorA)->post('/role-delegations', [
        'role_id' => $role->id,
        'recipient_email' => $recipient->email,
    ])->assertSessionHasNoErrors();

    $first = RoleDelegation::active()->where('user_id', $recipient->id)->firstOrFail();
    $this->actingAs($delegatorA)->delete("/role-delegations/{$first->id}");

    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeFalse();

    // Now a second delegator can grant it again — the partial unique index
    // only blocks duplicate *active* rows.
    $this->actingAs($delegatorB)->post('/role-delegations', [
        'role_id' => $role->id,
        'recipient_email' => $recipient->email,
    ])->assertSessionHasNoErrors();

    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeTrue();
});

it('records telemetry when a delegation is revoked', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create();
    $role = Role::findByName(Roles::GROWER);
    $delegation = RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => $role->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($delegator)->delete("/role-delegations/{$delegation->id}");

    $event = TelemetryEvent::where('event', Telemetry::ROLE_DELEGATION_REVOKED)->latest('id')->first();
    expect($event)->not->toBeNull();
    expect($event->context['revoked_by_role'])->toBe('delegator');
});

// ============== Admin oversight ==============

it('shows the admin role-delegations index to USERS_MANAGE holders', function () {
    $admin = User::factory()->superuser()->create();
    $delegator = User::factory()->grower()->create(['name' => 'Delegator Dan']);
    $recipient = User::factory()->grower()->create(['name' => 'Recipient Ravi']);
    RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => Role::findByName(Roles::GROWER)->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($admin)
        ->get('/admin/role-delegations')
        ->assertOk()
        ->assertSee('Recipient Ravi')
        ->assertSee('Delegator Dan')
        ->assertSee(Roles::GROWER);
});

it('forbids non-admins from the admin role-delegations index', function () {
    $user = User::factory()->grower()->create();
    $this->actingAs($user)->get('/admin/role-delegations')->assertForbidden();
});

it('renders the admin sidebar link for users-manage holders', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get('/admin/role-delegations')
        ->assertOk()
        ->assertSee(route('admin.role-delegations.index'));
});

// ============== Profile UI ==============

it('shows the delegate-role form for users who hold a delegatable role', function () {
    $user = User::factory()->grower()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Role delegations')
        ->assertSee('profile-delegations', escape: false)
        ->assertSee('Delegate role');
});

it('explains the empty state to users with no delegatable role to grant', function () {
    $user = User::factory()->create(); // no roles

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Role delegations')
        ->assertSeeText("You don't hold any delegatable roles right now");
});

it('lists delegations granted on the profile page with a revoke control', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->grower()->create(['name' => 'Receiver Person']);
    RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => Role::findByName(Roles::GROWER)->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($delegator)
        ->get('/profile')
        ->assertOk()
        ->assertSeeText("You've delegated to")
        ->assertSee('Receiver Person')
        ->assertSee('delegations-granted', escape: false)
        ->assertSee('revoke-delegation-granted', escape: false);
});

it('lists delegations received on the profile page with a renounce control', function () {
    $delegator = User::factory()->grower()->create(['name' => 'Giver Person']);
    $recipient = User::factory()->grower()->create();
    RoleDelegation::factory()->create([
        'user_id' => $recipient->id,
        'role_id' => Role::findByName(Roles::GROWER)->id,
        'delegated_by' => $delegator->id,
    ]);

    $this->actingAs($recipient)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Delegated to you')
        ->assertSee('Giver Person')
        ->assertSee('delegations-received', escape: false)
        ->assertSee('revoke-delegation-received', escape: false);
});
