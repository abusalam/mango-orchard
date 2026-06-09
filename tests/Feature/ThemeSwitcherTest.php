<?php

declare(strict_types=1);

use App\Models\User;

// ============== Bootstrap script in <head> ==============

it('ships the theme bootstrap inline script in the site layout head', function () {
    $body = $this->get(route('home'))->assertOk()->getContent();

    // The bootstrap reads the cookie and flips the `dark` class on
    // <html> BEFORE first paint to avoid FOUC for dark-mode users.
    expect($body)
        ->toContain('theme_preference=')
        ->toContain('prefers-color-scheme: dark')
        ->toContain('classList.toggle(\'dark\'');
});

it('ships the bootstrap on the welcome page too (separate file, same hook)', function () {
    $body = $this->get('/')->assertOk()->getContent();
    expect($body)->toContain('theme_preference=');
});

it('ships the bootstrap in the admin layout', function () {
    $admin = User::factory()->superuser()->create();
    $body = $this->actingAs($admin)->get(route('admin.settings.edit'))->assertOk()->getContent();
    expect($body)->toContain('theme_preference=');
});

it('ships the bootstrap in the guest (auth) layout', function () {
    $body = $this->get(route('login'))->assertOk()->getContent();
    expect($body)->toContain('theme_preference=');
});

// ============== Switcher widget renders ==============

it('renders the theme switcher in the site nav with all three option buttons', function () {
    // Each option is now a literal button in the HTML (loop unrolled
    // so each can carry its own icon and check-mark marker). All
    // three option testids are present server-side.
    $this->get(route('home'))
        ->assertOk()
        ->assertSee('data-testid="theme-switcher"', escape: false)
        ->assertSee('data-testid="theme-switcher-button"', escape: false)
        ->assertSee('data-testid="theme-option-auto"', escape: false)
        ->assertSee('data-testid="theme-option-light"', escape: false)
        ->assertSee('data-testid="theme-option-dark"', escape: false);
});

it('renders the theme switcher in the admin header', function () {
    $admin = User::factory()->superuser()->create();
    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('data-testid="theme-switcher"', escape: false);
});

// ============== Cookie pass-through ==============

it('accepts the theme_preference cookie unencrypted (exempt from EncryptCookies)', function () {
    // Smoke check: the request succeeds with the cookie set and the
    // bootstrap script is still in the response — i.e. Laravel didn't
    // 500 on a decrypt failure for our JS-set cookie.
    $body = $this->withUnencryptedCookies(['theme_preference' => 'dark'])
        ->get(route('home'))
        ->assertOk()
        ->getContent();

    expect($body)->toContain('theme_preference=');
});
