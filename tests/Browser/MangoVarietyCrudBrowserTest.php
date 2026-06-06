<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;

it('lets a guest browse the varieties index without management controls', function () {
    MangoVariety::factory()->create(['name' => 'Browser Alphonso', 'slug' => 'browser-alphonso', 'origin' => 'Browser Land']);

    visit('/varieties')
        ->assertSee('All mango varieties')
        ->assertSee('Browser Alphonso')
        ->assertSee('Browser Land')
        ->assertDontSeeIn('main', 'New variety')
        ->assertDontSeeIn('main', 'Delete');
});

it('lets a guest view a public variety detail page', function () {
    $variety = MangoVariety::factory()->create([
        'name' => 'Detail Variety',
        'slug' => 'detail-variety',
        'origin' => 'Somewhere Tropical',
        'flavor' => 'A tasting note unique to this browser test.',
    ]);

    visit(route('varieties.show', $variety))
        ->assertSee('Detail Variety')
        ->assertSee('Somewhere Tropical')
        ->assertSee('A tasting note unique to this browser test.')
        ->assertDontSeeIn('main', 'Delete');
});

it('bounces a guest from the create page to the login page', function () {
    visit(route('varieties.create'))
        ->assertPathIs('/login');
});

it('lets an authenticated user create a variety end-to-end through the form', function () {
    User::factory()->superuser()->create([
        'email' => 'curator@example.com',
        'password' => bcrypt('curator-password'),
    ]);

    visit('/login')
        ->type('email', 'curator@example.com')
        ->type('password', 'curator-password')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit(route('varieties.create'))
        ->assertSee('Add a new mango variety')
        ->type('name', 'UI-Made Mango')
        ->type('origin', 'Test City, Earth')
        ->type('season', 'Apr – Jun')
        ->select('season_start', '4')
        ->select('season_end', '6')
        ->type('flavor', 'A mango invented by a browser test, tangy and tart.')
        ->type('tags', 'Test, Synthetic')
        ->select('theme', 'emerald')
        ->press('Save variety')
        ->assertPathIs('/varieties/ui-made-mango')
        ->assertSee('UI-Made Mango')
        ->assertSee('Test City, Earth')
        ->assertSee('A mango invented by a browser test, tangy and tart.')
        ->assertSee('Test')
        ->assertSee('Synthetic');

    expect(MangoVariety::where('name', 'UI-Made Mango')->exists())->toBeTrue();
});

it('shows server-side validation errors for a duplicate name', function () {
    User::factory()->superuser()->create([
        'email' => 'val@example.com',
        'password' => bcrypt('val-password-x'),
    ]);
    MangoVariety::factory()->create(['name' => 'Already Taken', 'slug' => 'already-taken']);

    visit('/login')
        ->type('email', 'val@example.com')
        ->type('password', 'val-password-x')
        ->press('Log in');

    visit(route('varieties.create'))
        ->type('name', 'Already Taken')
        ->type('origin', 'Anywhere')
        ->type('season', 'Apr – Jun')
        ->select('season_start', '4')
        ->select('season_end', '6')
        ->type('flavor', 'Tasting note that will be rejected.')
        ->press('Save variety')
        ->assertPathIs('/varieties/create')
        ->assertSee('The name has already been taken');
});

it('lets an authenticated user edit a variety end-to-end', function () {
    User::factory()->superuser()->create([
        'email' => 'curator2@example.com',
        'password' => bcrypt('curator2-password'),
    ]);

    $variety = MangoVariety::factory()->create([
        'name' => 'Old Browser Name',
        'slug' => 'old-browser-name',
        'origin' => 'Old Origin',
    ]);

    visit('/login')
        ->type('email', 'curator2@example.com')
        ->type('password', 'curator2-password')
        ->press('Log in');

    visit(route('varieties.edit', $variety))
        ->assertSee('Edit Old Browser Name')
        ->clear('name')
        ->type('name', 'Renamed Variety')
        ->clear('origin')
        ->type('origin', 'New Origin')
        ->press('Save variety')
        ->assertSee('Renamed Variety')
        ->assertSee('New Origin')
        ->assertSee('Updated Renamed Variety.');

    expect($variety->fresh()->name)->toBe('Renamed Variety');
});

it('shows the Edit and Delete controls only to authenticated users', function () {
    MangoVariety::factory()->create(['name' => 'Visible Variety', 'slug' => 'visible-variety']);

    visit('/varieties')
        ->assertSee('Visible Variety')
        ->assertDontSeeIn('main', 'Edit')
        ->assertDontSeeIn('main', 'Delete');

    User::factory()->superuser()->create([
        'email' => 'auth-viewer@example.com',
        'password' => bcrypt('viewer-password'),
    ]);

    visit('/login')
        ->type('email', 'auth-viewer@example.com')
        ->type('password', 'viewer-password')
        ->press('Log in');

    visit('/varieties')
        ->assertSee('Visible Variety')
        ->assertSee('Edit')
        ->assertSee('Delete');
});
