<?php

declare(strict_types=1);

use App\Models\Listing;
use App\Models\MangoVariety;
use App\Models\User;

it('walks an authenticated grower through creating a listing end-to-end', function () {
    MangoVariety::factory()->create(['name' => 'Browser Alphonso', 'slug' => 'browser-alphonso']);
    User::factory()->grower()->create([
        'name' => 'Grower Person',
        'email' => 'grower@example.com',
        'password' => bcrypt('grower-pw-1234'),
    ]);

    visit('/login')
        ->type('email', 'grower@example.com')
        ->type('password', 'grower-pw-1234')
        ->press('Log in')
        ->assertPathIs('/dashboard');

    visit(route('my.listings.create'))
        ->assertSee('List a mango variety')
        ->select('mango_variety_id', (string) MangoVariety::firstWhere('name', 'Browser Alphonso')->id)
        ->type('farm_name', 'Browser Test Orchards')
        ->type('location', 'Konkan, India')
        ->type('description', 'Family-run grove showcased by a Pest browser test.')
        ->select('availability_start_month', '4')
        ->select('availability_end_month', '6')
        ->type('price_per_kg', '450.00')
        ->type('quantity_available_kg', '500')
        ->radio('status', Listing::STATUS_PUBLISHED)
        ->press('Save listing')
        ->assertPathIs('/my/listings')
        ->assertSee('Browser Test Orchards')
        ->assertSee('Saved listing for Browser Alphonso.');

    expect(Listing::where('farm_name', 'Browser Test Orchards')->exists())->toBeTrue();
});

it('shows a freshly-created published listing on the public marketplace', function () {
    User::factory()->create([
        'email' => 'pub@example.com',
        'password' => bcrypt('pub-pw-12345'),
    ]);
    $variety = MangoVariety::factory()->create(['name' => 'Public Test Variety', 'slug' => 'public-test-variety']);

    Listing::factory()->create([
        'mango_variety_id' => $variety->id,
        'farm_name' => 'Public Showcase Farm',
        'location' => 'Coastal Test Region',
        'contact_email' => 'showcase@example.com',
    ]);

    visit(route('listings.index'))
        ->assertSee('Marketplace')
        ->assertSee('Public Showcase Farm')
        ->click('Public Showcase Farm')
        ->assertPathBeginsWith('/listings/')
        ->assertSee('Coastal Test Region')
        ->assertSee('showcase@example.com')
        ->assertSee('Public Test Variety');
});

it("does not show another grower's draft listing on the public marketplace", function () {
    $owner = User::factory()->create();
    Listing::factory()->draft()->create([
        'user_id' => $owner->id,
        'farm_name' => 'Secret Draft Farm',
    ]);

    visit(route('listings.index'))
        ->assertSee('Marketplace')
        ->assertDontSeeIn('main', 'Secret Draft Farm');
});

it('hides the "List your harvest" CTA from unonboarded users (they get the Finish onboarding pill instead)', function () {
    User::factory()->unonboarded()->create([
        'email' => 'onboard-me@example.com',
        'password' => bcrypt('onboard-pw-12'),
    ]);

    visit('/login')
        ->type('email', 'onboard-me@example.com')
        ->type('password', 'onboard-pw-12')
        ->press('Log in')
        ->assertPathIs('/onboarding/profile');

    visit('/varieties')
        ->assertSee('Finish onboarding')
        ->assertDontSeeIn('main', 'List your harvest');
});
