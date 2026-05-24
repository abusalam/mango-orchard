<?php

declare(strict_types=1);

use App\Models\User;

it('renders the login page', function () {
    visit('/login')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertSee('Log in');
});

it('logs an existing user in and lands on the dashboard', function () {
    User::factory()->create([
        'email' => 'existing@example.com',
        'password' => bcrypt('secret-pw-1234'),
    ]);

    visit('/login')
        ->type('email', 'existing@example.com')
        ->type('password', 'secret-pw-1234')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->assertSee('Dashboard');
});

it('shows a validation error on a bad password', function () {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    visit('/login')
        ->type('email', 'real@example.com')
        ->type('password', 'wrong-password')
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertSee('credentials do not match');
});

it('renders the register page', function () {
    visit('/register')
        ->assertSee('Name')
        ->assertSee('Email')
        ->assertSee('Password')
        ->assertSee('Confirm Password')
        ->assertSee('Register');
});

it('registers a new user and lands inside the onboarding wizard', function () {
    visit('/register')
        ->type('name', 'Brand New')
        ->type('email', 'brandnew@example.com')
        ->type('password', 'long-enough-pw')
        ->type('password_confirmation', 'long-enough-pw')
        ->press('Register')
        ->assertPathIs('/onboarding/profile')
        ->assertSee('Hi Brand New')
        ->assertSee('Where are you mango-watching from?');
});

it('logs an authenticated user out', function () {
    User::factory()->create([
        'name' => 'Bye User',
        'email' => 'bye@example.com',
        'password' => bcrypt('byebyepass'),
    ]);

    visit('/login')
        ->type('email', 'bye@example.com')
        ->type('password', 'byebyepass')
        ->press('Log in')
        ->assertPathIs('/dashboard')
        ->click('Bye User')
        ->press('Log Out')
        ->assertPathIs('/');
});
