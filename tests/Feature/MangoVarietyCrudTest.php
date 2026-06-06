<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;

it('lists existing varieties on the public index', function () {
    MangoVariety::factory()->create(['name' => 'Test Alphonso', 'slug' => 'test-alphonso']);
    MangoVariety::factory()->create(['name' => 'Test Kesar', 'slug' => 'test-kesar']);

    $this->get(route('varieties.index'))
        ->assertOk()
        ->assertSee('Test Alphonso')
        ->assertSee('Test Kesar')
        ->assertSee('2 varieties in the orchard');
});

it('renders an empty-state on the public index when nothing is seeded', function () {
    $this->get(route('varieties.index'))
        ->assertOk()
        ->assertSeeText('No varieties yet.');
});

it('shows a public detail page', function () {
    $variety = MangoVariety::factory()->create([
        'name' => 'Detail Test',
        'slug' => 'detail-test',
        'origin' => 'Test Origin',
        'flavor' => 'Tastes like a unit test.',
        'tags' => ['Crisp', 'Fictional'],
    ]);

    $this->get(route('varieties.show', $variety))
        ->assertOk()
        ->assertSee('Detail Test')
        ->assertSee('Test Origin')
        ->assertSeeText('Tastes like a unit test.')
        ->assertSee('Crisp')
        ->assertSee('Fictional');
});

it('returns 404 for an unknown slug', function () {
    $this->get('/varieties/does-not-exist')->assertNotFound();
});

it('redirects guests away from the create form', function () {
    $this->get(route('varieties.create'))
        ->assertRedirect(route('login'));
});

it('redirects guests away from the edit form', function () {
    $variety = MangoVariety::factory()->create();

    $this->get(route('varieties.edit', $variety))
        ->assertRedirect(route('login'));
});

it('rejects store requests from guests', function () {
    $this->post(route('varieties.store'), validVarietyAttributes())
        ->assertRedirect(route('login'));

    expect(MangoVariety::count())->toBe(0);
});

it('rejects destroy requests from guests', function () {
    $variety = MangoVariety::factory()->create();

    $this->delete(route('varieties.destroy', $variety))
        ->assertRedirect(route('login'));

    expect(MangoVariety::whereKey($variety->id)->exists())->toBeTrue();
});

it('lets an authenticated user view the create form', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->get(route('varieties.create'))
        ->assertOk()
        ->assertSee('Add a new mango variety');
});

it('persists a variety on store and redirects to its detail page', function () {
    $payload = validVarietyAttributes(['name' => 'Indian Pride', 'origin' => 'Kerala, India']);

    $response = $this->actingAs(User::factory()->superuser()->create())
        ->post(route('varieties.store'), $payload);

    $variety = MangoVariety::firstWhere('name', 'Indian Pride');

    expect($variety)->not->toBeNull()
        ->and($variety->slug)->toBe('indian-pride')
        ->and($variety->origin)->toBe('Kerala, India')
        ->and($variety->tags)->toBe(['Sweet', 'Aromatic']);

    $response->assertRedirect(route('varieties.show', $variety))
        ->assertSessionHas('status', 'Added Indian Pride.');
});

it('validates required fields on store', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->post(route('varieties.store'), [])
        ->assertSessionHasErrors(['name', 'origin', 'season', 'season_start', 'season_end', 'flavor', 'theme']);

    expect(MangoVariety::count())->toBe(0);
});

it('rejects a season_end that precedes season_start', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->post(route('varieties.store'), validVarietyAttributes([
            'season_start' => 8,
            'season_end' => 4,
        ]))
        ->assertSessionHasErrors('season_end');
});

it('rejects an unknown theme', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->post(route('varieties.store'), validVarietyAttributes(['theme' => 'plaid']))
        ->assertSessionHasErrors('theme');
});

it('rejects a duplicate name on store', function () {
    MangoVariety::factory()->create(['name' => 'Duplicate']);

    $this->actingAs(User::factory()->superuser()->create())
        ->post(route('varieties.store'), validVarietyAttributes(['name' => 'Duplicate']))
        ->assertSessionHasErrors('name');
});

it('lets an authenticated user view the edit form prefilled', function () {
    $variety = MangoVariety::factory()->create(['name' => 'Editable']);

    $this->actingAs(User::factory()->superuser()->create())
        ->get(route('varieties.edit', $variety))
        ->assertOk()
        ->assertSee('Edit Editable')
        ->assertSee('value="Editable"', false);
});

it('updates an existing variety', function () {
    $variety = MangoVariety::factory()->create(['name' => 'Old Name', 'origin' => 'Old Origin']);

    $payload = validVarietyAttributes([
        'name' => 'New Name',
        'origin' => 'New Origin',
        'tags' => 'Refreshed, Tangy, Crisp',
    ]);

    $this->actingAs(User::factory()->superuser()->create())
        ->put(route('varieties.update', $variety), $payload)
        ->assertRedirect();

    $variety->refresh();

    expect($variety->name)->toBe('New Name')
        ->and($variety->origin)->toBe('New Origin')
        ->and($variety->tags)->toBe(['Refreshed', 'Tangy', 'Crisp']);
});

it('allows keeping the same name on update', function () {
    $variety = MangoVariety::factory()->create(['name' => 'Stable Name']);

    $this->actingAs(User::factory()->superuser()->create())
        ->put(
            route('varieties.update', $variety),
            validVarietyAttributes(['name' => 'Stable Name', 'origin' => 'Tweaked Origin']),
        )
        ->assertRedirect();

    expect($variety->fresh()->origin)->toBe('Tweaked Origin');
});

it('rejects a name collision on update', function () {
    MangoVariety::factory()->create(['name' => 'Existing']);
    $other = MangoVariety::factory()->create(['name' => 'Other']);

    $this->actingAs(User::factory()->superuser()->create())
        ->put(route('varieties.update', $other), validVarietyAttributes(['name' => 'Existing']))
        ->assertSessionHasErrors('name');
});

it('deletes a variety', function () {
    $variety = MangoVariety::factory()->create();

    $this->actingAs(User::factory()->superuser()->create())
        ->delete(route('varieties.destroy', $variety))
        ->assertRedirect(route('varieties.index'))
        ->assertSessionHas('status');

    expect(MangoVariety::whereKey($variety->id)->exists())->toBeFalse();
});

function validVarietyAttributes(array $overrides = []): array
{
    return array_merge([
        'name' => 'Sample Mango',
        'origin' => 'Test, Earth',
        'season' => 'Mar – May',
        'season_start' => 3,
        'season_end' => 5,
        'flavor' => 'Sweet, juicy, and entirely fictional.',
        'tags' => 'Sweet, Aromatic',
        'theme' => 'sunrise',
    ], $overrides);
}
