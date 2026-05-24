<?php

declare(strict_types=1);

use Database\Seeders\MangoVarietySeeder;

const VARIETIES = [
    'Alphonso',
    'Kesar',
    'Ataulfo',
    'Tommy Atkins',
    'Haden',
    'Keitt',
    'Kent',
    'Carabao',
    'Chaunsa',
    'Langra',
    'Dasheri',
    'Nam Dok Mai',
];

beforeEach(fn () => $this->seed(MangoVarietySeeder::class));

it('serves the welcome page successfully', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee('Mango Orchard', false)
        ->assertSee('Twelve mangoes worth knowing', false);
});

it('renders every seeded mango variety', function (string $variety) {
    $this->get('/')->assertSee($variety, false);
})->with(VARIETIES);

it('renders the season calendar with every variety row', function () {
    $response = $this->get('/');

    foreach (VARIETIES as $variety) {
        $response->assertSee($variety, false);
    }

    $response->assertSee('When each variety peaks', false);
});

it('renders the picking-guide tips', function () {
    $this->get('/')
        ->assertSeeText('Squeeze gently')
        ->assertSeeText('Smell the stem end')
        ->assertSeeText('Look at the shoulders')
        ->assertSeeText('Skip the fridge');
});

it('links the hero CTAs to the in-page sections', function () {
    $this->get('/')
        ->assertSee('href="#varieties"', false)
        ->assertSee('href="#season"', false);
});

it('shows a Get started link to guests', function () {
    $this->get('/')
        ->assertSee('Get started')
        ->assertSee('href="'.route('register').'"', false)
        ->assertDontSee('Finish onboarding')
        ->assertDontSee('New variety');
});

it('shows the Finish onboarding link to authed users who have not finished', function () {
    $user = \App\Models\User::factory()->unonboarded()->create();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('Finish onboarding')
        ->assertSee('href="'.route('onboarding.start').'"', false)
        ->assertDontSee('Get started')
        ->assertDontSee('New variety');
});

it('shows the New variety link to fully onboarded users with manage permission', function () {
    $user = \App\Models\User::factory()->superuser()->create();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertSee('New variety')
        ->assertDontSee('Finish onboarding')
        ->assertDontSee('Get started');
});

it('hides the New variety link from onboarded users without manage permission', function () {
    $user = \App\Models\User::factory()->create();

    $this->actingAs($user)->get('/')
        ->assertOk()
        ->assertDontSee('New variety')
        ->assertDontSee('Finish onboarding');
});

it('surfaces Dashboard, Profile, and Log out for any onboarded user', function () {
    $user = \App\Models\User::factory()->create(['name' => 'Logged In User']);

    $response = $this->actingAs($user)->get('/');

    $response->assertSee('Dashboard')
        ->assertSee('Logged In User')
        ->assertSee('Profile')
        ->assertSee('Log out')
        ->assertSee('href="'.route('dashboard').'"', false)
        ->assertSee('href="'.route('profile.edit').'"', false)
        ->assertSee('action="'.route('logout').'"', false);
});

it('shows the superuser role badge next to a superuser name', function () {
    $user = \App\Models\User::factory()->superuser()->create(['name' => 'Boss Person']);

    $this->actingAs($user)->get('/')
        ->assertSee('Boss Person')
        ->assertSee('data-testid="user-role-badge"', false)
        ->assertSee(\App\Roles::SUPERUSER);
});

it('shows the editor role badge next to an editor name', function () {
    $user = \App\Models\User::factory()->editor()->create(['name' => 'Editor Person']);

    $this->actingAs($user)->get('/')
        ->assertSee('Editor Person')
        ->assertSee('data-testid="user-role-badge"', false)
        ->assertSee(\App\Roles::EDITOR);
});

it('renders no role badge for a user with no roles', function () {
    $user = \App\Models\User::factory()->create(['name' => 'Plain Person']);

    $this->actingAs($user)->get('/')
        ->assertSee('Plain Person')
        ->assertDontSee('data-testid="user-role-badge"', false);
});

it('exposes a mobile menu toggle for small viewports', function () {
    $this->get('/')
        ->assertSee('data-testid="mobile-menu-toggle"', false)
        ->assertSee('data-testid="mobile-menu"', false);
});
