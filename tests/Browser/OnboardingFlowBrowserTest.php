<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;
use Database\Seeders\MangoVarietySeeder;

beforeEach(fn () => $this->seed(MangoVarietySeeder::class));

it('walks a brand-new user end-to-end from register through both onboarding steps', function () {
    visit('/register')
        ->type('name', 'Wizard Tester')
        ->type('email', 'wizard@example.com')
        ->type('password', 'wizard-pw-1234')
        ->type('password_confirmation', 'wizard-pw-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile')
        ->assertSee('Hi Wizard Tester')
        ->assertSee('Where are you mango-watching from?')
        ->assertAttribute('[data-onboarding-step="account"]', 'data-onboarding-step', 'account')
        ->assertAttribute('[data-onboarding-step="profile"]', 'data-onboarding-step', 'profile')
        ->assertAttribute('[data-onboarding-step="preferences"]', 'data-onboarding-step', 'preferences')
        ->type('region', 'Mumbai, India')
        ->radio('expertise', 'enthusiast')
        ->press('Continue')
        ->assertPathIs('/onboarding/preferences')
        ->assertSee('A few preferences')
        ->select('favorite_variety_id', (string) MangoVariety::firstWhere('name', 'Himsagar')->id)
        ->check('notify_seasonal')
        ->press('Finish setup')
        ->assertPathIs('/dashboard')
        ->assertSee("You're all set");

    $user = User::firstWhere('email', 'wizard@example.com');

    expect($user->region)->toBe('Mumbai, India')
        ->and($user->expertise)->toBe('enthusiast')
        ->and($user->notify_seasonal)->toBeTrue()
        ->and($user->subscribe_newsletter)->toBeFalse()
        ->and($user->hasCompletedOnboarding())->toBeTrue();
});

it('redirects an unonboarded user from a gated page back into the wizard', function () {
    User::factory()->unonboarded()->create([
        'email' => 'unonboarded@example.com',
        'password' => bcrypt('unonboarded-pw'),
    ]);

    visit('/login')
        ->type('email', 'unonboarded@example.com')
        ->type('password', 'unonboarded-pw')
        ->press('Log in')
        ->assertPathIs('/onboarding/profile')
        ->assertSee('Where are you mango-watching from?');

    visit('/dashboard')->assertPathIs('/onboarding/profile');
    visit('/varieties/create')->assertPathIs('/onboarding/profile');
});

it('resumes a partially-onboarded user at the preferences step', function () {
    User::factory()->unonboarded()->create([
        'email' => 'partial@example.com',
        'password' => bcrypt('partial-pw-12'),
        'region' => 'Bangkok',
        'expertise' => 'professional',
    ]);

    visit('/login')
        ->type('email', 'partial@example.com')
        ->type('password', 'partial-pw-12')
        ->press('Log in')
        ->assertPathIs('/onboarding/preferences')
        ->assertSee('A few preferences');
});

it('lets a user navigate back from preferences to profile via the back link', function () {
    User::factory()->unonboarded()->create([
        'email' => 'navback@example.com',
        'password' => bcrypt('navback-pw-12'),
        'region' => 'Lima',
        'expertise' => 'beginner',
    ]);

    visit('/login')
        ->type('email', 'navback@example.com')
        ->type('password', 'navback-pw-12')
        ->press('Log in')
        ->assertPathIs('/onboarding/preferences')
        ->click('← Back')
        ->assertPathIs('/onboarding/profile');
});

it('allows finishing onboarding without picking a favorite variety', function () {
    User::factory()->unonboarded()->create([
        'email' => 'nofav@example.com',
        'password' => bcrypt('nofav-pw-1234'),
        'region' => 'Honolulu',
        'expertise' => 'beginner',
    ]);

    visit('/login')
        ->type('email', 'nofav@example.com')
        ->type('password', 'nofav-pw-1234')
        ->press('Log in')
        ->assertPathIs('/onboarding/preferences')
        ->press('Finish setup')
        ->assertPathIs('/dashboard');

    $user = User::firstWhere('email', 'nofav@example.com');

    expect($user->hasCompletedOnboarding())->toBeTrue()
        ->and($user->favorite_variety_id)->toBeNull();
});

it('surfaces a Finish onboarding shortcut on the welcome page for unonboarded users', function () {
    User::factory()->unonboarded()->create([
        'email' => 'unfinished@example.com',
        'password' => bcrypt('unfinished-pw-1'),
    ]);

    visit('/login')
        ->type('email', 'unfinished@example.com')
        ->type('password', 'unfinished-pw-1')
        ->press('Log in')
        ->assertPathIs('/onboarding/profile');

    visit('/')
        ->assertSee('Finish onboarding')
        ->click('Finish onboarding')
        ->assertPathIs('/onboarding/profile');
});

it('skips onboarding entirely for users who have already completed it', function () {
    User::factory()->create([
        'email' => 'done@example.com',
        'password' => bcrypt('done-pw-1234'),
    ]);

    visit('/login')
        ->type('email', 'done@example.com')
        ->type('password', 'done-pw-1234')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit('/onboarding')->assertPathIs('/dashboard');
});
