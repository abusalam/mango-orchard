<?php

declare(strict_types=1);

use App\Models\User;
use App\Permissions;
use App\Roles;
use Spatie\Permission\Models\Role;

it('grants the superuser role to the very first registered user', function () {
    expect(User::count())->toBe(0);

    $this->post('/register', [
        'name' => 'Founder',
        'email' => 'founder@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ]);

    $first = User::firstWhere('email', 'founder@example.com');
    expect($first->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('promotes the first registrant even when other roleless users already exist', function () {
    // Simulates: roles seeded, some roleless users in the DB (e.g. imported),
    // but nobody has the superuser role yet. The first person to register through
    // the form should claim it.
    User::factory()->count(3)->create();

    expect(Role::findByName(Roles::SUPERUSER)->users()->exists())->toBeFalse();

    $this->post('/register', [
        'name' => 'Late Founder',
        'email' => 'late-founder@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ]);

    $founder = User::firstWhere('email', 'late-founder@example.com');
    expect($founder->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('does not auto-promote anyone once a superuser already exists', function () {
    User::factory()->superuser()->create(['email' => 'existing-super@example.com']);

    $this->post('/register', [
        'name' => 'Newcomer',
        'email' => 'newcomer@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ]);

    $newcomer = User::firstWhere('email', 'newcomer@example.com');
    expect($newcomer->hasRole(Roles::SUPERUSER))->toBeFalse()
        ->and($newcomer->getRoleNames()->toArray())->toBe([]);
});

it('promotes the first registrant if a previous user was deleted leaving no superuser', function () {
    // Simulates an admin wiping the original superuser account before
    // a new person registers; the next registration should reclaim the role.
    $original = User::factory()->superuser()->create();
    $original->delete();

    expect(Role::findByName(Roles::SUPERUSER)->users()->exists())->toBeFalse();

    $this->post('/register', [
        'name' => 'Successor',
        'email' => 'successor@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
    ]);

    expect(User::firstWhere('email', 'successor@example.com')->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('does not assign the superuser role to factory-created users (only registration triggers it)', function () {
    expect(Role::findByName(Roles::SUPERUSER)->users()->exists())->toBeFalse();

    User::factory()->count(5)->create();

    expect(Role::findByName(Roles::SUPERUSER)->users()->exists())->toBeFalse();
});

it('blocks unauthenticated users from variety writes', function () {
    $this->post(route('varieties.store'), [])->assertRedirect(route('login'));
});

it('blocks authed users without varieties.manage from variety writes', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('varieties.store'), [])
        ->assertForbidden();
});

it('lets a user with varieties.manage write through the controller', function () {
    $this->actingAs(User::factory()->curator()->create())
        ->get(route('varieties.create'))
        ->assertOk();
});

it('blocks non-managers from the admin section', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('lets a superuser see the admin users list', function () {
    $superuser = User::factory()->superuser()->create(['name' => 'Boss']);
    User::factory()->create(['name' => 'Some Person']);

    $this->actingAs($superuser)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertSee('Boss')
        ->assertSee('Some Person')
        ->assertSee(Roles::SUPERUSER);
});

it('lets a superuser open the edit-user page', function () {
    $superuser = User::factory()->superuser()->create();
    $target = User::factory()->create(['name' => 'Target Person']);

    $this->actingAs($superuser)
        ->get(route('admin.users.edit', $target))
        ->assertOk()
        ->assertSee('Target Person')
        ->assertSee(Roles::CURATOR)
        ->assertSee(Roles::VIEWER);
});

it('assigns roles to a user through the admin form', function () {
    $superuser = User::factory()->superuser()->create();
    $target = User::factory()->create();

    $this->actingAs($superuser)
        ->put(route('admin.users.update', $target), ['roles' => [Roles::CURATOR]])
        ->assertRedirect(route('admin.users.index'));

    expect($target->refresh()->hasRole(Roles::CURATOR))->toBeTrue();
});

it('removes all roles when none are submitted', function () {
    $superuser = User::factory()->superuser()->create();
    $target = User::factory()->curator()->create();

    expect($target->hasRole(Roles::CURATOR))->toBeTrue();

    $this->actingAs($superuser)
        ->put(route('admin.users.update', $target), [])
        ->assertRedirect();

    expect($target->refresh()->getRoleNames()->toArray())->toBe([]);
});

it('prevents a superuser from removing the superuser role from themselves', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)
        ->from(route('admin.users.edit', $superuser))
        ->put(route('admin.users.update', $superuser), ['roles' => [Roles::CURATOR]])
        ->assertSessionHasErrors('roles');

    expect($superuser->refresh()->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('lets a superuser create a new role with selected permissions', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)
        ->post(route('admin.roles.store'), [
            'name' => 'moderator',
            'permissions' => [Permissions::VARIETIES_MANAGE],
        ])
        ->assertRedirect(route('admin.roles.index'));

    $role = Role::findByName('moderator');
    expect($role->permissions->pluck('name')->toArray())->toBe([Permissions::VARIETIES_MANAGE]);
});

it('validates the role name format', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)
        ->post(route('admin.roles.store'), [
            'name' => 'Invalid Name With Spaces',
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');
});

it('rejects a duplicate role name on store', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)
        ->post(route('admin.roles.store'), [
            'name' => Roles::SUPERUSER,
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');
});

it('rejects an unknown permission on role store', function () {
    $superuser = User::factory()->superuser()->create();

    $this->actingAs($superuser)
        ->post(route('admin.roles.store'), [
            'name' => 'moderator',
            'permissions' => ['something.not.real'],
        ])
        ->assertSessionHasErrors('permissions.0');
});

it('updates a non-protected role and resyncs permissions', function () {
    $superuser = User::factory()->superuser()->create();
    $curator = Role::findByName(Roles::CURATOR);

    $this->actingAs($superuser)
        ->put(route('admin.roles.update', $curator), [
            'name' => 'curator',
            'permissions' => [],
        ])
        ->assertRedirect();

    expect($curator->fresh()->permissions->toArray())->toBe([]);
});

it('refuses to update the superuser role', function () {
    $superuser = User::factory()->superuser()->create();
    $super = Role::findByName(Roles::SUPERUSER);

    $this->actingAs($superuser)
        ->put(route('admin.roles.update', $super), [
            'name' => 'super',
            'permissions' => [],
        ])
        ->assertForbidden();

    expect(Role::findByName(Roles::SUPERUSER))->not->toBeNull();
});

it('deletes a role that has no users', function () {
    $superuser = User::factory()->superuser()->create();
    $role = Role::create(['name' => 'temporary', 'guard_name' => 'web']);

    $this->actingAs($superuser)
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::where('name', 'temporary')->exists())->toBeFalse();
});

it('refuses to delete a role with assigned users', function () {
    $superuser = User::factory()->superuser()->create();
    $role = Role::create(['name' => 'in-use', 'guard_name' => 'web']);
    User::factory()->create()->assignRole($role);

    $this->actingAs($superuser)
        ->delete(route('admin.roles.destroy', $role))
        ->assertStatus(422);

    expect(Role::where('name', 'in-use')->exists())->toBeTrue();
});

it('refuses to delete the superuser role', function () {
    $superuser = User::factory()->superuser()->create();
    $super = Role::findByName(Roles::SUPERUSER);

    $this->actingAs($superuser)
        ->delete(route('admin.roles.destroy', $super))
        ->assertForbidden();

    expect(Role::findByName(Roles::SUPERUSER))->not->toBeNull();
});

it('blocks the admin/roles section from users without roles.manage', function () {
    $this->actingAs(User::factory()->curator()->create())
        ->get(route('admin.roles.index'))
        ->assertForbidden();
});
