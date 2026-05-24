<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\Settings;

beforeEach(function () {
    app(Settings::class)->forget();
});

it('defaults to form autofill disabled', function () {
    expect(app(Settings::class)->formAutofill())->toBeFalse();
});

it('exposes the autofill meta tag on every layout when the setting is on', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);

    // Guest layout (login form)
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('<meta name="form-autofill" content="1">', escape: false);

    // Site layout (welcome)
    $this->get('/')
        ->assertOk()
        ->assertSee('<meta name="form-autofill" content="1">', escape: false);

    // App layout (dashboard) — requires auth
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('<meta name="form-autofill" content="1">', escape: false);

    // Admin layout
    $admin = User::factory()->superuser()->create();
    $this->actingAs($admin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('<meta name="form-autofill" content="1">', escape: false);

    // Onboarding layout
    $unonboarded = User::factory()->unonboarded()->create();
    $this->actingAs($unonboarded)
        ->get('/onboarding/profile')
        ->assertOk()
        ->assertSee('<meta name="form-autofill" content="1">', escape: false);
});

it('does not render the autofill meta tag when the setting is off', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, false);

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('name="form-autofill"', escape: false);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('name="form-autofill"', escape: false);
});

it('admin settings page exposes the autofill toggle to settings managers', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get('/admin/settings')
        ->assertOk()
        ->assertSee('Prefill empty form fields')
        ->assertSee('data-testid="autofill-toggle"', escape: false);
});

it('admin can toggle form autofill on and off through the settings form', function () {
    $admin = User::factory()->superuser()->create();
    expect(app(Settings::class)->formAutofill())->toBeFalse();

    $this->actingAs($admin)
        ->put('/admin/settings', [
            'captcha_enabled' => '0',
            'captcha_autosolve' => '0',
            'form_autofill' => '1',
        ])
        ->assertRedirect('/admin/settings');

    app(Settings::class)->forget();
    expect(app(Settings::class)->formAutofill())->toBeTrue();

    $this->actingAs($admin)
        ->put('/admin/settings', [
            'captcha_enabled' => '0',
            'captcha_autosolve' => '0',
            'form_autofill' => '0',
        ])
        ->assertRedirect('/admin/settings');

    app(Settings::class)->forget();
    expect(app(Settings::class)->formAutofill())->toBeFalse();
});

it('non-admins cannot reach the settings form to toggle autofill', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/admin/settings', [
            'form_autofill' => '1',
        ])
        ->assertForbidden();

    expect(app(Settings::class)->formAutofill())->toBeFalse();
});

it('serves the bundled autofill.js asset', function () {
    $manifest = json_decode(file_get_contents(public_path('build/manifest.json')), true);
    expect($manifest)->toHaveKey('resources/js/app.js');

    $appJs = $manifest['resources/js/app.js']['file'];
    $contents = file_get_contents(public_path('build/'.$appJs));

    // The autofill module's identifying meta-tag name should be present.
    expect($contents)->toContain('form-autofill');
});
