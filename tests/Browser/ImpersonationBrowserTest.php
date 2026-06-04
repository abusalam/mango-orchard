<?php

declare(strict_types=1);

use App\Models\User;

it('walks a superuser through impersonating a user from /admin/users and back', function () {
    User::factory()->superuser()->create([
        'name' => 'Super Boss',
        'email' => 'superboss@example.com',
        'password' => bcrypt('boss-pw-12345'),
    ]);
    User::factory()->grower()->create([
        'name' => 'Target Grower',
        'email' => 'targetgrower@example.com',
        'password' => bcrypt('target-pw-12'),
    ]);

    visit('/login')
        ->type('email', 'superboss@example.com')
        ->type('password', 'boss-pw-12345')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit('/admin/users')
        ->assertSee('Target Grower')
        ->press('Impersonate')
        ->assertPathIs('/dashboard')
        ->assertSee('You are now acting as Target Grower')
        ->assertSee('Acting as')
        ->assertSee('Target Grower')
        ->assertSee('Super Boss')
        ->press('Return to my account')
        ->assertPathIs('/dashboard')
        ->assertSee('Back to Super Boss');
});

it('lets an impersonator pick a role and lands on the first matching user', function () {
    User::factory()->impersonator()->create([
        'email' => 'role-imp@example.com',
        'password' => bcrypt('role-imp-pw-1'),
    ]);
    User::factory()->grower()->create([
        'name' => 'First Grower User',
        'email' => 'fg@example.com',
    ]);

    visit('/login')
        ->type('email', 'role-imp@example.com')
        ->type('password', 'role-imp-pw-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit('/admin/impersonate')
        ->assertSee('Impersonate any grower')
        ->press('Impersonate any grower')
        ->assertPathIs('/dashboard')
        ->assertSee('First Grower User')
        ->assertSee('role: grower');
});

it('does not show an Apply for the impersonator role on the profile page', function () {
    User::factory()->create([
        'name' => 'Plain Jane',
        'email' => 'plain@example.com',
        'password' => bcrypt('plain-pw-123'),
    ]);

    visit('/login')
        ->type('email', 'plain@example.com')
        ->type('password', 'plain-pw-123')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit('/profile')
        ->assertSee('Request a role')
        ->assertSee('grower')
        ->assertSee('curator')
        ->assertDontSeeIn('main', 'impersonator');
});
