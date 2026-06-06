<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;

it('redirects a fresh registration through to the onboarding profile step', function () {
    $response = $this->post('/register', [
        'name' => 'New Grower',
        'email' => 'new@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('onboarding.start', absolute: false));

    $this->followingRedirects()
        ->get(route('onboarding.start'))
        ->assertOk()
        ->assertSee('Tell us about you', false);
});

it('redirects unonboarded users away from gated app pages', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertRedirect(route('onboarding.start'));

    $this->actingAs($user)->get(route('varieties.create'))
        ->assertRedirect(route('onboarding.start'));
});

it('lets unonboarded users browse the homepage and public varieties pages', function () {
    $user = User::factory()->unonboarded()->create();
    $variety = MangoVariety::factory()->create();

    $this->actingAs($user)->get(route('home'))->assertOk();
    $this->actingAs($user)->get(route('varieties.index'))->assertOk();
    $this->actingAs($user)->get(route('varieties.show', $variety))->assertOk();
});

it('lets unonboarded users still access onboarding routes and log out', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)->get(route('onboarding.profile'))->assertOk();
    $this->actingAs($user)->post(route('logout'))->assertRedirect('/');
});

it('start redirects a user with no profile data to the profile step', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)->get(route('onboarding.start'))
        ->assertRedirect(route('onboarding.profile'));
});

it('start redirects a user with profile data on file to the preferences step', function () {
    $user = User::factory()->unonboarded()->create([
        'region' => 'Mumbai, India',
        'expertise' => 'enthusiast',
    ]);

    $this->actingAs($user)->get(route('onboarding.start'))
        ->assertRedirect(route('onboarding.preferences'));
});

it('start redirects fully onboarded users to the dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('onboarding.start'))
        ->assertRedirect(route('dashboard'));
});

it('validates the profile step', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.profile'), [])
        ->assertSessionHasErrors(['region', 'expertise']);
});

it('rejects an unknown expertise value', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)
        ->post(route('onboarding.profile'), ['region' => 'Lima', 'expertise' => 'expert'])
        ->assertSessionHasErrors('expertise');
});

it('saves the profile step and advances to preferences', function () {
    $user = User::factory()->unonboarded()->create();

    $response = $this->actingAs($user)->post(route('onboarding.profile'), [
        'region' => 'Ratnagiri, India',
        'expertise' => 'grower',
    ]);

    $response->assertRedirect(route('onboarding.preferences'));

    $user->refresh();
    expect($user->region)->toBe('Ratnagiri, India')
        ->and($user->expertise)->toBe('grower')
        ->and($user->hasCompletedOnboarding())->toBeFalse();
});

it('rejects an unknown favorite variety id', function () {
    $user = User::factory()->unonboarded()->create([
        'region' => 'Lima',
        'expertise' => 'enthusiast',
    ]);

    $this->actingAs($user)
        ->post(route('onboarding.preferences'), ['favorite_variety_id' => 999999])
        ->assertSessionHasErrors('favorite_variety_id');
});

it('finishes onboarding on preferences submit and unlocks the app', function () {
    $variety = MangoVariety::factory()->create();
    $user = User::factory()->unonboarded()->create([
        'region' => 'Bangkok',
        'expertise' => 'enthusiast',
    ]);

    $response = $this->actingAs($user)->post(route('onboarding.preferences'), [
        'favorite_variety_id' => $variety->id,
        'notify_seasonal' => '1',
        'subscribe_newsletter' => '0',
    ]);

    $response->assertRedirect(route('dashboard'))
        ->assertSessionHas('status');

    $user->refresh();
    expect($user->hasCompletedOnboarding())->toBeTrue()
        ->and($user->favorite_variety_id)->toBe($variety->id)
        ->and($user->notify_seasonal)->toBeTrue()
        ->and($user->subscribe_newsletter)->toBeFalse();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

it('accepts a null favorite_variety_id', function () {
    $user = User::factory()->unonboarded()->create([
        'region' => 'Hawaii',
        'expertise' => 'beginner',
    ]);

    $this->actingAs($user)->post(route('onboarding.preferences'), [
        'favorite_variety_id' => '',
    ])->assertRedirect(route('dashboard'));

    expect($user->fresh()->hasCompletedOnboarding())->toBeTrue();
});

it('does not interfere with guest browsing of public pages', function () {
    $this->get(route('home'))->assertOk();
    $this->get(route('varieties.index'))->assertOk();
});

it('does not interfere with authed onboarded users browsing the app', function () {
    $user = User::factory()->superuser()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
    $this->actingAs($user)->get(route('varieties.create'))->assertOk();
});
