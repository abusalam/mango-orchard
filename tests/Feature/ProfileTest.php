<?php

use App\Models\MangoVariety;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Telemetry\Telemetry;

test('profile page is displayed', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->get('/profile');

    $response->assertOk();
});

test('profile information can be updated', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();

    $this->assertSame('Test User', $user->name);
    $this->assertSame('test@example.com', $user->email);
    $this->assertNull($user->email_verified_at);
});

test('email verification status is unchanged when the email address is unchanged', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->patch('/profile', [
            'name' => 'Test User',
            'email' => $user->email,
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $this->assertNotNull($user->refresh()->email_verified_at);
});

test('user can delete their account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->delete('/profile', [
            'password' => 'password',
        ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/');

    $this->assertGuest();
    $this->assertNull($user->fresh());
});

test('correct password must be provided to delete account', function () {
    $user = User::factory()->create();

    $response = $this
        ->actingAs($user)
        ->from('/profile')
        ->delete('/profile', [
            'password' => 'wrong-password',
        ]);

    $response
        ->assertSessionHasErrorsIn('userDeletion', 'password')
        ->assertRedirect('/profile');

    $this->assertNotNull($user->fresh());
});

test('profile page renders the preferences form prefilled with current values', function () {
    $alphonso = MangoVariety::factory()->create(['name' => 'Alphonso', 'origin' => 'Konkan, India']);
    $user = User::factory()->create([
        'region' => 'Pune, India',
        'expertise' => 'enthusiast',
        'favorite_variety_id' => $alphonso->id,
        'notify_seasonal' => true,
        'subscribe_newsletter' => false,
    ]);

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Orchard preferences')
        ->assertSee('Pune, India', escape: false)
        ->assertSee('Alphonso')
        ->assertSee('Save preferences');
});

test('user can update their onboarding preferences', function () {
    $oldFav = MangoVariety::factory()->create(['name' => 'Old Fav']);
    $newFav = MangoVariety::factory()->create(['name' => 'New Fav']);
    $user = User::factory()->create([
        'region' => 'Old Region',
        'expertise' => 'beginner',
        'favorite_variety_id' => $oldFav->id,
        'notify_seasonal' => false,
        'subscribe_newsletter' => false,
    ]);
    $originallyCompletedAt = $user->onboarding_completed_at;

    $this->actingAs($user)
        ->patch('/profile/preferences', [
            'region' => 'New Region',
            'expertise' => 'professional',
            'favorite_variety_id' => $newFav->id,
            'notify_seasonal' => '1',
            'subscribe_newsletter' => '1',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile')
        ->assertSessionHas('status', 'preferences-updated');

    $user->refresh();
    expect($user->region)->toBe('New Region');
    expect($user->expertise)->toBe('professional');
    expect($user->favorite_variety_id)->toBe($newFav->id);
    expect($user->notify_seasonal)->toBeTrue();
    expect($user->subscribe_newsletter)->toBeTrue();
    // Updating preferences must not touch the onboarding-complete flag.
    expect($user->onboarding_completed_at?->toIso8601String())
        ->toBe($originallyCompletedAt?->toIso8601String());
});

test('unchecked notification boxes correctly persist as false', function () {
    $user = User::factory()->create([
        'notify_seasonal' => true,
        'subscribe_newsletter' => true,
    ]);

    $this->actingAs($user)
        ->patch('/profile/preferences', [
            'region' => $user->region,
            'expertise' => $user->expertise,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    $user->refresh();
    expect($user->notify_seasonal)->toBeFalse();
    expect($user->subscribe_newsletter)->toBeFalse();
});

test('preferences update requires region and expertise', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/profile')
        ->patch('/profile/preferences', [
            'region' => '',
            'expertise' => 'not-a-real-level',
        ])
        ->assertSessionHasErrors(['region', 'expertise'])
        ->assertRedirect('/profile');
});

test('updating preferences records a telemetry event', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->patch('/profile/preferences', [
        'region' => 'Telemetry Town',
        'expertise' => 'grower',
    ]);

    $event = TelemetryEvent::where('event', Telemetry::PREFERENCES_UPDATED)
        ->where('user_id', $user->id)
        ->latest('id')
        ->first();

    expect($event)->not->toBeNull();
    expect($event->context['region'])->toBe('Telemetry Town');
    expect($event->context['expertise'])->toBe('grower');
});

test('guests cannot reach the preferences update endpoint', function () {
    $this->patch('/profile/preferences', [
        'region' => 'X',
        'expertise' => 'beginner',
    ])->assertRedirect('/login');
});

test('profile sidebar wires up hash-based active-link tracking', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/profile');
    $response->assertOk();
    $body = $response->getContent();

    // Aside reads window.location.hash on init and listens for changes.
    expect($body)->toContain("x-data=\"{ active: window.location.hash.slice(1) }\"");
    expect($body)->toContain('x-on:hashchange.window="active = window.location.hash.slice(1)"');

    // Each sidebar link has an x-bind:class that flips on the section name,
    // and an aria-current binding for accessibility.
    foreach (['profile-information', 'preferences', 'role-applications', 'role-delegations', 'password'] as $section) {
        expect($body)->toContain("active === '{$section}' ? 'bg-orange-50 text-orange-900' : 'text-stone-700 hover:bg-stone-100'");
        expect($body)->toContain("active === '{$section}' ? 'true' : null");
    }

    // Danger zone uses the rose palette instead of orange to stay distinct.
    expect($body)->toContain("active === 'danger-zone' ? 'bg-rose-100 text-rose-900' : 'text-rose-700 hover:bg-rose-50'");
});
