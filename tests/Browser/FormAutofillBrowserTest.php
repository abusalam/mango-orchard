<?php

declare(strict_types=1);

use App\Models\Listing;
use App\Models\MangoVariety;
use App\Models\User;
use App\Settings\Settings;

beforeEach(fn () => app(Settings::class)->forget());

/**
 * Wait until the autofill JS has had its initial pass over the page. The
 * script sets `window.__autofill.done = true` (set in a `finally` so it
 * fires whether autofill was enabled or not). Without this, value()
 * assertions race the DOMContentLoaded handler under heavy suite load.
 */
function waitForAutofill(object $page, float $timeoutSeconds = 2.0): void
{
    $deadline = microtime(true) + $timeoutSeconds;
    while (microtime(true) < $deadline) {
        if ($page->script('!!(window.__autofill && window.__autofill.done)') === true) {
            return;
        }
        usleep(50_000);
    }
    throw new RuntimeException("Autofill did not signal completion within {$timeoutSeconds}s");
}

it('does not autofill any form fields when the setting is off', function () {
    $page = visit('/register')->assertSee('Register');

    expect($page->value('input[name="name"]'))->toBe('');
    expect($page->value('input[name="email"]'))->toBe('');
});

it('prefills empty text and email inputs on the register form when the setting is on', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);

    $page = visit('/register')->assertSee('Register');
    waitForAutofill($page);

    expect($page->value('input[name="name"]'))->not->toBe('');
    expect($page->value('input[name="email"]'))->toMatch('/@/');
});

it('never autofills password fields even when enabled', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);

    $page = visit('/register');
    waitForAutofill($page);

    expect($page->value('input[name="password"]'))->toBe('');
    expect($page->value('input[name="password_confirmation"]'))->toBe('');
});

it('does not overwrite optional/filter selects with autofill on', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);
    MangoVariety::factory()->create(['name' => 'Filter Test Variety', 'slug' => 'filter-test-variety']);
    Listing::factory()->create(['farm_name' => 'Filter Farm']);

    // /listings has an optional "All varieties" filter dropdown that
    // auto-submits on change. Autofill must NOT pick a specific variety.
    $page = visit('/listings')->assertSee('Marketplace');
    waitForAutofill($page);
    expect($page->value('select[name="variety"]'))->toBe('');
    // And the page should still be on /listings — no auto-redirect from a
    // surprise filter submission.
    $page->assertPathIs('/listings');
});

it('does not overwrite the telemetry event filter when autofill is on', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);
    User::factory()->superuser()->create([
        'email' => 'filter-admin@example.com',
        'password' => bcrypt('filter-admin-1'),
    ]);

    visit('/login')
        ->type('email', 'filter-admin@example.com')
        ->type('password', 'filter-admin-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    $page = visit('/admin/telemetry')->assertSee('Activity');
    waitForAutofill($page);
    expect($page->value('select[name="event"]'))->toBe('');
    $page->assertPathIs('/admin/telemetry');
});

it('does not overwrite the optional favorite-variety select on the profile preferences form', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);
    MangoVariety::factory()->create(['name' => 'Optional Test Variety', 'slug' => 'optional-test-variety']);
    User::factory()->create([
        'email' => 'optselect@example.com',
        'password' => bcrypt('optselect-pw-1'),
        'favorite_variety_id' => null,
    ]);

    visit('/login')
        ->type('email', 'optselect@example.com')
        ->type('password', 'optselect-pw-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    $page = visit('/profile')->assertSee('Orchard preferences');
    waitForAutofill($page);
    expect($page->value('select[name="favorite_variety_id"]'))->toBe('');
});

it('prefills the listing create form for a grower when autofill is on', function () {
    app(Settings::class)->set(Settings::FORM_AUTOFILL, true);
    MangoVariety::factory()->create(['name' => 'Autofill Alphonso', 'slug' => 'autofill-alphonso']);
    User::factory()->grower()->create([
        'email' => 'autofiller@example.com',
        'password' => bcrypt('autofill-pw-1'),
    ]);

    $page = visit('/login')
        ->type('email', 'autofiller@example.com')
        ->type('password', 'autofill-pw-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    // Visit the listing create form. Variety select + farm/location/description
    // should be populated by the autofill JS.
    $page = visit(route('my.listings.create'))->assertSee('List a mango variety');
    waitForAutofill($page);

    expect($page->value('input[name="farm_name"]'))->not->toBe('');
    expect($page->value('input[name="location"]'))->not->toBe('');
    expect($page->value('textarea[name="description"]'))->not->toBe('');
    // Select should land on a real option (non-empty value).
    expect($page->value('select[name="mango_variety_id"]'))->not->toBe('');
});
