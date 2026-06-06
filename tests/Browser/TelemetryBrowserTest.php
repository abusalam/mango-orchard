<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Telemetry\Telemetry;

it('records a full chain of telemetry through register + onboarding + variety create', function () {
    visit('/register')
        ->type('name', 'Telemetry Walker')
        ->type('email', 'walker@example.com')
        ->type('password', 'walker-pw-1234')
        ->type('password_confirmation', 'walker-pw-1234')
        ->press('Register')
        ->assertPathIs('/onboarding/profile')
        ->type('region', 'Hometown')
        ->radio('expertise', 'enthusiast')
        ->press('Continue')
        ->assertPathIs('/onboarding/preferences')
        ->press('Finish setup')
        ->assertPathIs('/dashboard');

    // First-ever registrant gets the superuser role, so they can create varieties.
    visit(route('varieties.create'))
        ->type('name', 'Telemetry Walker Mango')
        ->type('origin', 'Walked Land')
        ->type('season', 'Apr – Jun')
        ->select('season_start', '4')
        ->select('season_end', '6')
        ->type('flavor', 'Bumpy, sweet, made by a browser test.')
        ->press('Save variety')
        ->assertPathIs('/varieties/telemetry-walker-mango');

    $user = User::firstWhere('email', 'walker@example.com');

    $events = TelemetryEvent::where('user_id', $user->id)->pluck('event')->all();
    expect($events)->toContain(Telemetry::AUTH_REGISTERED)
        ->and($events)->toContain(Telemetry::AUTH_LOGIN_SUCCEEDED)
        ->and($events)->toContain(Telemetry::ONBOARDING_PROFILE_SAVED)
        ->and($events)->toContain(Telemetry::ONBOARDING_PREFERENCES_SAVED)
        ->and($events)->toContain(Telemetry::ONBOARDING_COMPLETED)
        ->and($events)->toContain(Telemetry::VARIETY_CREATED);
});

it('lets a superuser open the activity feed and see recorded events', function () {
    User::factory()->superuser()->create([
        'name' => 'Activity Admin',
        'email' => 'activity-admin@example.com',
        'password' => bcrypt('activity-pw-1'),
    ]);
    MangoVariety::factory()->create(['name' => 'Activity Sample', 'slug' => 'activity-sample']);

    visit('/login')
        ->type('email', 'activity-admin@example.com')
        ->type('password', 'activity-pw-1')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit(route('admin.telemetry.index'))
        ->assertSee('Activity')
        ->assertSee(Telemetry::VARIETY_CREATED)
        ->assertSee(Telemetry::AUTH_LOGIN_SUCCEEDED)
        ->assertSee('Activity Admin')
        ->assertVisible('[data-testid="telemetry-row"]:first-of-type');
});

it('hides the Activity sidebar link from curators and blocks the page', function () {
    User::factory()->curator()->create([
        'email' => 'curator-noact@example.com',
        'password' => bcrypt('curator-noact-1'),
    ]);

    visit('/login')
        ->type('email', 'curator-noact@example.com')
        ->type('password', 'curator-noact-1')
        ->press('Log in');

    visit('/varieties')->assertDontSeeIn('main', 'Activity');

    visit(route('admin.telemetry.index'))->assertSee('403');
});

it('filters the activity feed by event via the dropdown', function () {
    User::factory()->superuser()->create([
        'email' => 'filter-admin@example.com',
        'password' => bcrypt('filter-admin-1'),
    ]);
    MangoVariety::factory()->create(); // creates a variety.created row

    visit('/login')
        ->type('email', 'filter-admin@example.com')
        ->type('password', 'filter-admin-1')
        ->press('Log in');

    visit(route('admin.telemetry.index'))
        ->assertSee(Telemetry::VARIETY_CREATED)
        ->assertSee(Telemetry::AUTH_LOGIN_SUCCEEDED)
        ->select('event', Telemetry::VARIETY_CREATED)
        ->assertPathIs('/admin/telemetry')
        ->assertSee(Telemetry::VARIETY_CREATED);
});
