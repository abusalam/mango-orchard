<?php

declare(strict_types=1);

use App\Captcha\Captcha;
use App\Models\User;
use App\Settings\Settings;

beforeEach(fn () => app(Settings::class)->forget());

it('hides the captcha field by default and lets login through', function () {
    User::factory()->create([
        'email' => 'no-captcha@example.com',
        'password' => bcrypt('regular-pw-12'),
    ]);

    visit('/login')
        ->assertDontSeeIn('main', 'Captcha')
        ->type('email', 'no-captcha@example.com')
        ->type('password', 'regular-pw-12')
        ->press('Log in')
        ->assertPathIs('/dashboard');
});

it('lets a superuser enable captcha + autosolve via the admin settings page', function () {
    User::factory()->superuser()->create([
        'name' => 'Admin Person',
        'email' => 'admin-toggle@example.com',
        'password' => bcrypt('admin-toggle-1'),
    ]);

    visit('/login')
        ->type('email', 'admin-toggle@example.com')
        ->type('password', 'admin-toggle-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit(route('admin.settings.edit'))
        ->assertSee('Captcha')
        ->assertSee('Autosolve captcha')
        ->check('captcha_enabled', '1')
        ->check('captcha_autosolve', '1')
        ->press('Save settings')
        ->assertPathIs('/admin/settings')
        ->assertSee('Settings saved.');

    expect(app(Settings::class)->captchaEnabled())->toBeTrue()
        ->and(app(Settings::class)->captchaAutosolve())->toBeTrue();
});

it('shows the captcha image and rejects a wrong answer when autosolve is off', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    User::factory()->create([
        'email' => 'target@example.com',
        'password' => bcrypt('target-pw-12'),
    ]);

    visit('/login')
        ->assertSee('Captcha')
        ->assertVisible('img[data-testid="captcha-image"]')
        ->assertDontSeeIn('main', 'Autosolve is on')
        ->type('email', 'target@example.com')
        ->type('password', 'target-pw-12')
        ->type(Captcha::FIELD, 'definitely-wrong')
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertSee('captcha');
});

it('renders the captcha input prefilled with the correct answer when autosolve is on', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    app(Settings::class)->set(Settings::CAPTCHA_AUTOSOLVE, true);
    User::factory()->create([
        'email' => 'autosolve-target@example.com',
        'password' => bcrypt('autosolve-pw-1'),
    ]);

    // The captcha input arrives non-empty (the prefilled answer) and the
    // user can submit without typing into it.
    visit('/login')
        ->assertSee('Captcha')
        ->assertVisible('img[data-testid="captcha-image"]')
        ->assertSee('prefilled with the correct answer')
        ->assertAttributeContains('input[data-testid="captcha-input"]', 'value', '')
        ->type('email', 'autosolve-target@example.com')
        ->type('password', 'autosolve-pw-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');
});

it('autosolve still validates server-side — tampering with the prefill still fails', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    app(Settings::class)->set(Settings::CAPTCHA_AUTOSOLVE, true);
    User::factory()->create([
        'email' => 'tamper@example.com',
        'password' => bcrypt('tamper-pw-12'),
    ]);

    // Replace the prefilled value with junk — the server should reject it.
    visit('/login')
        ->assertSee('prefilled with the correct answer')
        ->clear(Captcha::FIELD)
        ->type(Captcha::FIELD, 'TAMPERED')
        ->type('email', 'tamper@example.com')
        ->type('password', 'tamper-pw-12')
        ->press('Log in')
        ->assertPathIs('/login')
        ->assertSee('captcha');
});

it('lets registration through with autosolve on (prefilled answer is correct)', function () {
    app(Settings::class)->set(Settings::CAPTCHA_ENABLED, true);
    app(Settings::class)->set(Settings::CAPTCHA_AUTOSOLVE, true);

    visit('/register')
        ->assertSee('Captcha')
        ->assertSee('prefilled with the correct answer')
        ->type('name', 'Auto User')
        ->type('email', 'auto@example.com')
        ->type('password', 'long-enough-pw-1')
        ->type('password_confirmation', 'long-enough-pw-1')
        ->press('Register')
        ->assertPathIs('/onboarding/profile');

    expect(User::where('email', 'auto@example.com')->exists())->toBeTrue();
});

it('grays out the autosolve toggle on the settings page when captcha is off', function () {
    User::factory()->superuser()->create([
        'email' => 'admin-disabled@example.com',
        'password' => bcrypt('admin-disabled-1'),
    ]);

    visit('/login')
        ->type('email', 'admin-disabled@example.com')
        ->type('password', 'admin-disabled-1')
        ->press('Log in');

    visit(route('admin.settings.edit'))
        ->assertVisible('label:has([data-testid="autosolve-toggle"]).opacity-50');
});

it('hides the Settings sidebar link from editors', function () {
    User::factory()->editor()->create([
        'email' => 'editor-nosettings@example.com',
        'password' => bcrypt('editor-only-12'),
    ]);

    visit('/login')
        ->type('email', 'editor-nosettings@example.com')
        ->type('password', 'editor-only-12')
        ->press('Log in');

    visit('/varieties')->assertDontSeeIn('main', 'Settings');
});
