<?php

declare(strict_types=1);

use App\Models\User;
use App\Roles;
use App\Settings\Settings;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

// The suite-wide beforeEach marks setup complete; these tests exercise the
// fresh-install path so they reset the flag first.
function freshInstall(): void
{
    app(Settings::class)->set(Settings::SITE_SETUP_COMPLETED, false);
}

it('redirects all traffic to /setup on a fresh install', function () {
    freshInstall();

    $this->get('/')->assertRedirect(route('setup.show'));
    $this->get('/login')->assertRedirect(route('setup.show'));
    $this->get('/varieties')->assertRedirect(route('setup.show'));
});

it('shows the setup wizard on a fresh install', function () {
    freshInstall();

    $this->get('/setup')
        ->assertOk()
        ->assertSee('Administrator account')
        ->assertSee('data-testid="setup-form"', false);
});

it('completes setup: creates a verified superuser, optional logo, signs in', function () {
    freshInstall();
    Storage::fake('public');

    $this->post('/setup', [
        'name' => 'First Admin',
        'email' => 'admin@example.gov.in',
        'password' => 'SuperSecret1!',
        'password_confirmation' => 'SuperSecret1!',
        'logo' => UploadedFile::fake()->image('logo.png', 512, 512),
    ])->assertRedirect(route('dashboard'));

    $admin = User::where('email', 'admin@example.gov.in')->firstOrFail();
    expect($admin->hasRole(Roles::SUPERUSER))->toBeTrue();
    expect($admin->email_verified_at)->not->toBeNull();
    expect($admin->hasCompletedOnboarding())->toBeTrue();
    $this->assertAuthenticatedAs($admin);

    $settings = app(Settings::class);
    expect($settings->setupCompleted())->toBeTrue();
    expect($settings->siteLogoPath())->toStartWith('branding/logo-');
    Storage::disk('public')->assertExists($settings->siteLogoPath());
});

it('works without a logo — monogram fallback applies', function () {
    freshInstall();

    $this->post('/setup', [
        'name' => 'First Admin',
        'email' => 'admin@example.gov.in',
        'password' => 'SuperSecret1!',
        'password_confirmation' => 'SuperSecret1!',
    ])->assertRedirect(route('dashboard'));

    expect(app(Settings::class)->siteLogoPath())->toBeNull();

    // Nav renders the generated monogram, not a broken img.
    $this->get('/')->assertSee('data-testid="site-logo-monogram"', false);
});

it('cannot be replayed once a user exists', function () {
    User::factory()->create(); // any user — flag may also already be true

    $this->get('/setup')->assertForbidden();
    $this->post('/setup', [
        'name' => 'Hijacker',
        'email' => 'evil@example.com',
        'password' => 'SuperSecret1!',
        'password_confirmation' => 'SuperSecret1!',
    ])->assertForbidden();
});

it('does not redirect normal traffic once setup is complete', function () {
    $this->get('/')->assertOk();
});

it('lets a settings admin upload and remove the site logo', function () {
    Storage::fake('public');
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->post(route('admin.settings.logo.update'), [
            'logo' => UploadedFile::fake()->image('brand.png', 512, 512),
        ])->assertRedirect(route('admin.settings.edit'));

    $path = app(Settings::class)->siteLogoPath();
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($admin)
        ->delete(route('admin.settings.logo.remove'))
        ->assertRedirect(route('admin.settings.edit'));

    expect(app(Settings::class)->siteLogoPath())->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
