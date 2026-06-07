<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Roles;

// ============== Page gating ==============

it('blocks a regular user from the access page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.monitoring.access.index'))
        ->assertForbidden();
});

it('lets a monitor-admin open the access page', function () {
    $admin = User::factory()->monitorAdmin()->create();
    User::factory()->count(3)->create();

    $this->actingAs($admin)
        ->get(route('admin.monitoring.access.index'))
        ->assertOk()
        ->assertSee('data-testid="access-table"', escape: false)
        ->assertSee('data-testid="monitoring-member-count"', escape: false);
});

// ============== Grant ==============

it('grants module access: assigns monitor role + creates profile', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $target = User::factory()->create(['name' => 'Target User']);

    expect($target->fresh()->hasRole(Roles::MONITOR))->toBeFalse();
    expect(MonitorProfile::where('user_id', $target->id)->exists())->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.access.grant', $target))
        ->assertRedirect();

    expect($target->fresh()->hasRole(Roles::MONITOR))->toBeTrue();
    expect(MonitorProfile::where('user_id', $target->id)->exists())->toBeTrue();
});

it('granting a user who already holds the role is idempotent', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $target = User::factory()->monitor()->create();
    MonitorProfile::create(['user_id' => $target->id]);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.access.grant', $target))
        ->assertRedirect();

    expect(MonitorProfile::where('user_id', $target->id)->count())->toBe(1);
});

// ============== Revoke ==============

it('revokes module access: drops role, deletes profile, detaches designations', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $target = User::factory()->monitor()->create();
    $d = Designation::factory()->create();
    MonitorProfile::create(['user_id' => $target->id]);
    $target->designations()->attach($d->id);

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.access.revoke', $target))
        ->assertRedirect();

    $fresh = $target->fresh();
    expect($fresh->hasRole(Roles::MONITOR))->toBeFalse();
    expect(MonitorProfile::where('user_id', $target->id)->exists())->toBeFalse();
    expect($fresh->designations)->toBeEmpty();
});

it('revoke also strips monitor-admin role', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $target = User::factory()->monitorAdmin()->create();
    MonitorProfile::create(['user_id' => $target->id]);

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.access.revoke', $target))
        ->assertRedirect();

    expect($target->fresh()->hasRole(Roles::MONITOR_ADMIN))->toBeFalse();
});

it('revoke leaves the child users\' enrolment + designations untouched', function () {
    // Reporting now flows through designations, not user-to-user, so a
    // revoked parent has no effect on children's profile or designations.
    $admin = User::factory()->monitorAdmin()->create();
    $parent = User::factory()->monitor()->create();
    $childA = User::factory()->monitor()->create();
    $childB = User::factory()->monitor()->create();
    monitorHierarchy([[$parent, null], [$childA, $parent], [$childB, $parent]]);

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.access.revoke', $parent))
        ->assertRedirect();

    expect(MonitorProfile::where('user_id', $childA->id)->exists())->toBeTrue();
    expect(MonitorProfile::where('user_id', $childB->id)->exists())->toBeTrue();
    expect($childA->fresh()->designations)->not->toBeEmpty();
    expect($childB->fresh()->designations)->not->toBeEmpty();
});

// ============== Filters on the page ==============

it('filters the access page by membership status', function () {
    $admin = User::factory()->monitorAdmin()->create();
    User::factory()->monitor()->create(['name' => 'IsInModule']);
    User::factory()->create(['name' => 'NotInModule']);

    $this->actingAs($admin)
        ->get(route('admin.monitoring.access.index', ['only' => 'members']))
        ->assertOk()
        ->assertSee('IsInModule')
        ->assertDontSee('NotInModule');

    $this->actingAs($admin)
        ->get(route('admin.monitoring.access.index', ['only' => 'non-members']))
        ->assertOk()
        ->assertSee('NotInModule')
        ->assertDontSee('IsInModule');
});

// ============== Role visibility (hide from self-apply / delegate) ==============

it('hides monitor role from the self-apply role list on profile', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('profile.edit'))->assertOk();

    expect(\App\Roles::nonApplicable())->toContain(\App\Roles::MONITOR)
        ->toContain(\App\Roles::MONITOR_ADMIN);
});

it('rejects a role-application POST for the monitor role', function () {
    $user = User::factory()->create();
    $monitorRoleId = \Spatie\Permission\Models\Role::where('name', \App\Roles::MONITOR)->value('id');

    $this->actingAs($user)
        ->post(route('role-applications.store'), ['role_id' => $monitorRoleId])
        ->assertSessionHasErrors('role_id');
});

it('rejects a peer delegation of the monitor role', function () {
    $sender = User::factory()->create();
    // sender must hold the role to attempt delegation — but even so, monitor isn't delegatable.
    $sender->assignRole(\App\Roles::MONITOR);
    $recipient = User::factory()->create();
    $monitorRoleId = \Spatie\Permission\Models\Role::where('name', \App\Roles::MONITOR)->value('id');

    $this->actingAs($sender)
        ->post(route('role-delegations.store'), [
            'role_id' => $monitorRoleId,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('role_id');

    expect(\App\Roles::delegatable())->not->toContain(\App\Roles::MONITOR);
});
