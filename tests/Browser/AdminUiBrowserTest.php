<?php

declare(strict_types=1);

use App\Models\User;
use App\Permissions;
use App\Roles;
use Spatie\Permission\Models\Role;

it('makes the very first registered user the superuser', function () {
    visit('/register')
        ->type('name', 'First Ever')
        ->type('email', 'first@example.com')
        ->type('password', 'first-pw-1234')
        ->type('password_confirmation', 'first-pw-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile');

    $first = User::firstWhere('email', 'first@example.com');

    expect($first->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('surfaces the superuser badge on the welcome nav after the first registration', function () {
    visit('/register')
        ->type('name', 'Boss From Birth')
        ->type('email', 'boss-birth@example.com')
        ->type('password', 'boss-birth-1234')
        ->type('password_confirmation', 'boss-birth-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile')
        ->type('region', 'Hometown')
        ->radio('expertise', 'enthusiast')
        ->press('Continue')
        ->press('Finish setup')
        ->assertPathIs('/dashboard');

    visit('/')
        ->assertSee('Boss From Birth')
        ->assertSee(Roles::SUPERUSER)
        // Admin lives inside the user dropdown — open it to reveal the link.
        ->click('Boss From Birth')
        ->assertSee('Admin');
});

it('does not promote the second registrant when a superuser already exists', function () {
    User::factory()->superuser()->create(['email' => 'incumbent@example.com']);

    visit('/register')
        ->type('name', 'Late Comer')
        ->type('email', 'late@example.com')
        ->type('password', 'late-pw-1234')
        ->type('password_confirmation', 'late-pw-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile');

    $late = User::firstWhere('email', 'late@example.com');

    expect($late->hasRole(Roles::SUPERUSER))->toBeFalse()
        ->and($late->getRoleNames()->toArray())->toBe([]);
});

it('promotes the first form-registrant even when roleless users exist in the DB', function () {
    // Simulates an installation where some users were imported but nobody has
    // ever held the superuser role.
    User::factory()->count(3)->create();

    visit('/register')
        ->type('name', 'Reclaiming Throne')
        ->type('email', 'reclaim@example.com')
        ->type('password', 'reclaim-pw-1234')
        ->type('password_confirmation', 'reclaim-pw-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile');

    expect(User::firstWhere('email', 'reclaim@example.com')->hasRole(Roles::SUPERUSER))->toBeTrue();
});

it('lets a superuser open the admin users section through the welcome nav', function () {
    User::factory()->superuser()->create([
        'name' => 'Boss Lady',
        'email' => 'boss@example.com',
        'password' => bcrypt('boss-pw-12345'),
    ]);
    User::factory()->create(['name' => 'Random Person']);

    visit('/login')
        ->type('email', 'boss@example.com')
        ->type('password', 'boss-pw-12345')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit('/')
        // Open the user dropdown to reach Admin.
        ->click('Boss Lady')
        ->click('Admin')
        ->assertPathIs('/admin/users')
        ->assertSee('Boss Lady')
        ->assertSee('Random Person')
        ->assertSee(Roles::SUPERUSER);
});

it('assigns the editor role to a user end-to-end through the UI', function () {
    $superuser = User::factory()->superuser()->create([
        'email' => 'admin1@example.com',
        'password' => bcrypt('admin1-pw-1234'),
    ]);
    $target = User::factory()->create(['name' => 'Promote Me']);

    visit('/login')
        ->type('email', 'admin1@example.com')
        ->type('password', 'admin1-pw-1234')
        ->press('Log in');

    visit(route('admin.users.edit', $target))
        ->assertSee('Promote Me')
        ->check('roles[]', Roles::EDITOR)
        ->press('Save roles')
        ->assertPathIs('/admin/users')
        ->assertSee("Updated roles for Promote Me.");

    expect($target->fresh()->hasRole(Roles::EDITOR))->toBeTrue();
});

it('creates a new role with selected permissions via the UI', function () {
    User::factory()->superuser()->create([
        'email' => 'admin2@example.com',
        'password' => bcrypt('admin2-pw-1234'),
    ]);

    visit('/login')
        ->type('email', 'admin2@example.com')
        ->type('password', 'admin2-pw-1234')
        ->press('Log in');

    visit(route('admin.roles.create'))
        ->type('name', 'browser-role')
        ->check('permissions[]', Permissions::VARIETIES_MANAGE)
        ->press('Save role')
        ->assertPathIs('/admin/roles')
        ->assertSee('browser-role')
        ->assertSee(Permissions::VARIETIES_MANAGE);

    $role = Role::findByName('browser-role');
    expect($role->permissions->pluck('name')->toArray())->toBe([Permissions::VARIETIES_MANAGE]);
});

it('hides the admin nav link from users without admin permissions', function () {
    User::factory()->editor()->create([
        'name' => 'Editor Person',
        'email' => 'editor-only@example.com',
        'password' => bcrypt('editor-only-pw'),
    ]);

    visit('/login')
        ->type('email', 'editor-only@example.com')
        ->type('password', 'editor-only-pw')
        ->press('Log in');

    visit('/')
        ->assertDontSeeIn('header', 'Admin')
        // Open the dropdown to reveal user-only actions; editor sees "New variety" but not "Admin".
        ->click('Editor Person')
        ->assertSee('New variety')
        ->assertDontSeeIn('header', 'Admin');
});

it('returns 403 when an editor tries to open the admin area', function () {
    User::factory()->editor()->create([
        'email' => 'editor-403@example.com',
        'password' => bcrypt('editor-403-pw'),
    ]);

    visit('/login')
        ->type('email', 'editor-403@example.com')
        ->type('password', 'editor-403-pw')
        ->press('Log in');

    visit('/admin/users')->assertSee('403');
});
