<?php

declare(strict_types=1);

use App\Models\User;
use App\Settings\Settings;

beforeEach(function () {
    app(Settings::class)->set(Settings::READONLY_MODE, true);
});

afterEach(function () {
    app(Settings::class)->set(Settings::READONLY_MODE, false);
});

// ============== Banner rendering ==============

it('shows the read-only banner site-wide while the setting is on', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('data-testid="readonly-banner"', escape: false)
        ->assertSee('Read-only mode is on.');

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-testid="readonly-banner"', escape: false);

    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('data-testid="readonly-banner"', escape: false);
});

it('hides the read-only banner when the setting is off', function () {
    app(Settings::class)->set(Settings::READONLY_MODE, false);

    $this->get('/')
        ->assertOk()
        ->assertDontSee('data-testid="readonly-banner"', escape: false);
});

// ============== Write blocking for non-superusers ==============

it('blocks a grower from creating a listing while read-only mode is on', function () {
    $grower = User::factory()->grower()->create();

    $this->actingAs($grower)
        ->post(route('my.listings.store'), [
            'mango_variety_id' => 1,
            'farm_name' => 'Test Farm',
            'location' => 'Malda',
            'available_from_month' => 5,
            'available_to_month' => 7,
        ])
        ->assertForbidden();
});

it('blocks an authed user from updating their profile while read-only mode is on', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Renamed',
            'email' => $user->email,
        ])
        ->assertForbidden();
});

it('blocks a curator from updating a variety while read-only mode is on', function () {
    $curator = User::factory()->curator()->create();

    $this->actingAs($curator)
        ->post(route('varieties.store'), [
            'name' => 'Test Mango',
            'origin' => 'Nowhere',
        ])
        ->assertForbidden();
});

// ============== Carve-outs ==============

it('still lets a superuser write while read-only mode is on', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->post(route('varieties.store'), [
            'name' => 'Admin Mango',
            'origin' => 'Anywhere',
            'season' => 'summer',
            'flavor_profile' => 'sweet',
            'description' => 'Created during read-only mode by superuser.',
        ])
        ->assertRedirect();
});

it('blocks a non-superuser from signing in while read-only mode is on', function () {
    User::factory()->create([
        'email' => 'rom-login@example.com',
        'password' => bcrypt('login-pw-1234'),
    ]);

    $response = $this->post('/login', [
        'email' => 'rom-login@example.com',
        'password' => 'login-pw-1234',
    ]);

    $response->assertSessionHasErrors('email');
    expect(auth()->check())->toBeFalse();
});

it('still lets a superuser sign in while read-only mode is on', function () {
    User::factory()->superuser()->create([
        'email' => 'rom-admin@example.com',
        'password' => bcrypt('admin-pw-1234'),
    ]);

    $this->post('/login', [
        'email' => 'rom-admin@example.com',
        'password' => 'admin-pw-1234',
    ])->assertRedirect('/dashboard');

    expect(auth()->check())->toBeTrue();
});

it('blocks self-registration of new accounts while read-only mode is on', function () {
    $this->post('/register', [
        'name' => 'Late Comer',
        'email' => 'too-late@example.com',
        'password' => 'first-secret-1',
        'password_confirmation' => 'first-secret-1',
    ])->assertForbidden();

    expect(User::where('email', 'too-late@example.com')->exists())->toBeFalse();
});

it('still allows sign out while read-only mode is on', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect('/');
});

it('lets a superuser toggle read-only mode back off via the settings form', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'readonly_mode' => '0',
        ])
        ->assertRedirect(route('admin.settings.edit'));

    expect(app(Settings::class)->readonlyMode())->toBeFalse();
});

// ============== Read traffic unaffected ==============

it('does not block GET requests in read-only mode', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
    $this->get(route('listings.index'))->assertOk();
    $this->get(route('varieties.index'))->assertOk();
});
