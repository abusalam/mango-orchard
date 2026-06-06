<?php

declare(strict_types=1);

use App\Models\RoleApplication;
use App\Models\User;
use App\Roles;
use Spatie\Permission\Models\Role;

// ============== Access-page gating ==============

it('blocks a regular user from the Mango Orchard access page', function () {
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.mango-orchard.access.index'))
        ->assertForbidden();
});

it('lets a superuser open the Mango Orchard access page', function () {
    $admin = User::factory()->superuser()->create();
    User::factory()->count(2)->create();

    $this->actingAs($admin)
        ->get(route('admin.mango-orchard.access.index'))
        ->assertOk()
        ->assertSee('data-testid="mango-access-table"', escape: false)
        ->assertSee('data-testid="mango-member-count"', escape: false);
});

// ============== Grant ==============

it('grants module access: assigns mango-orchard-member', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->create();

    expect($target->hasRole(Roles::MANGO_ORCHARD_MEMBER))->toBeFalse();

    $this->actingAs($admin)
        ->post(route('admin.mango-orchard.access.grant', $target))
        ->assertRedirect();

    expect($target->fresh()->hasRole(Roles::MANGO_ORCHARD_MEMBER))->toBeTrue();
});

it('grants module access and pre-assigns sub-roles in one go', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.mango-orchard.access.grant', $target), [
            'sub_roles' => [Roles::GROWER, Roles::CURATOR],
        ])
        ->assertRedirect();

    $fresh = $target->fresh();
    expect($fresh->hasRole(Roles::MANGO_ORCHARD_MEMBER))->toBeTrue();
    expect($fresh->hasRole(Roles::GROWER))->toBeTrue();
    expect($fresh->hasRole(Roles::CURATOR))->toBeTrue();
});

it('rejects an unknown sub-role on grant', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->create();

    $this->actingAs($admin)
        ->post(route('admin.mango-orchard.access.grant', $target), [
            'sub_roles' => ['nonsense'],
        ])
        ->assertSessionHasErrors('sub_roles.0');
});

// ============== Revoke ==============

it('revokes Mango Orchard access: drops member + every sub-role', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->grower()->create();
    $target->assignRole(Roles::CURATOR);

    $this->actingAs($admin)
        ->delete(route('admin.mango-orchard.access.revoke', $target))
        ->assertRedirect();

    $fresh = $target->fresh();
    expect($fresh->hasRole(Roles::MANGO_ORCHARD_MEMBER))->toBeFalse();
    expect($fresh->hasRole(Roles::GROWER))->toBeFalse();
    expect($fresh->hasRole(Roles::CURATOR))->toBeFalse();
});

it('cancels pending sub-role applications on revoke', function () {
    $admin = User::factory()->superuser()->create();
    $target = User::factory()->mangoOrchardMember()->create();
    RoleApplication::factory()->create([
        'user_id' => $target->id,
        'role_id' => Role::findByName(Roles::GROWER)->id,
        'status' => RoleApplication::STATUS_PENDING,
    ]);

    $this->actingAs($admin)
        ->delete(route('admin.mango-orchard.access.revoke', $target))
        ->assertRedirect();

    expect(RoleApplication::where('user_id', $target->id)->where('status', RoleApplication::STATUS_REJECTED)->exists())->toBeTrue();
    expect(RoleApplication::where('user_id', $target->id)->where('status', RoleApplication::STATUS_PENDING)->exists())->toBeFalse();
});

// ============== Self-apply gate ==============

it('hides grower from the apply-for list for a non-member', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertDontSee('value="'.Role::findByName(Roles::GROWER)->id.'"', escape: false);
});

it('rejects a self-apply POST for grower from a non-member with a helpful message', function () {
    $user = User::factory()->create();
    $growerId = Role::findByName(Roles::GROWER)->id;

    $this->actingAs($user)
        ->from('/profile')
        ->post('/role-applications', ['role_id' => $growerId])
        ->assertSessionHasErrors('role_id');
});

it('lets a member self-apply for grower', function () {
    $user = User::factory()->mangoOrchardMember()->create();
    $growerId = Role::findByName(Roles::GROWER)->id;

    $this->actingAs($user)
        ->post('/role-applications', ['role_id' => $growerId])
        ->assertRedirect('/profile')
        ->assertSessionHas('status');

    expect(RoleApplication::where('user_id', $user->id)->exists())->toBeTrue();
});

it('rejects a self-apply for the membership role itself (admin-only)', function () {
    $user = User::factory()->create();
    $memberRoleId = Role::findByName(Roles::MANGO_ORCHARD_MEMBER)->id;

    $this->actingAs($user)
        ->post('/role-applications', ['role_id' => $memberRoleId])
        ->assertSessionHasErrors('role_id');
});

// ============== Delegation gate ==============

it('rejects delegating grower to a non-member recipient', function () {
    $delegator = User::factory()->grower()->create();
    $recipient = User::factory()->create();
    $growerId = Role::findByName(Roles::GROWER)->id;

    $this->actingAs($delegator)
        ->from('/profile')
        ->post('/role-delegations', [
            'role_id' => $growerId,
            'recipient_email' => $recipient->email,
        ])
        ->assertSessionHasErrors('recipient_email');

    expect($recipient->fresh()->hasRole(Roles::GROWER))->toBeFalse();
});

// ============== Filter ==============

it('filters the access page by membership status', function () {
    $admin = User::factory()->superuser()->create();
    User::factory()->mangoOrchardMember()->create(['name' => 'AlreadyMember']);
    User::factory()->create(['name' => 'StillOutside']);

    $this->actingAs($admin)
        ->get(route('admin.mango-orchard.access.index', ['only' => 'members']))
        ->assertOk()
        ->assertSee('AlreadyMember')
        ->assertDontSee('StillOutside');

    $this->actingAs($admin)
        ->get(route('admin.mango-orchard.access.index', ['only' => 'non-members']))
        ->assertOk()
        ->assertSee('StillOutside')
        ->assertDontSee('AlreadyMember');
});
