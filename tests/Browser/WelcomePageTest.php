<?php

declare(strict_types=1);

use Database\Seeders\MangoVarietySeeder;

beforeEach(fn () => $this->seed(MangoVarietySeeder::class));

const BROWSER_VARIETIES = [
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

it('boots the home page with the expected branding and hero copy', function () {
    visit('/')
        ->assertTitleContains('A field guide to mango varieties')
        ->assertSee('A field guide')
        ->assertSee('The world tastes')
        ->assertSee('sweeter')
        ->assertSee('mango season')
        ->assertSeeLink('Browse varieties')
        ->assertSeeLink('See season guide');
});

it('renders every featured mango variety card', function (string $variety) {
    visit('/')->assertSee($variety);
})->with(BROWSER_VARIETIES);

it('exposes the primary nav links (All varieties + Marketplace)', function () {
    visit('/')
        ->assertSeeLink('All varieties')
        ->assertSeeLink('Marketplace');
});

it('jumps to the varieties section when its CTA is clicked', function () {
    visit('/')
        ->click('Browse varieties')
        ->assertFragmentIs('varieties');
});

it('renders the season calendar with rows for each variety', function () {
    visit('/')
        ->assertSee('When each variety peaks')
        ->assertVisible('#season table')
        ->assertSeeIn('#season table thead', 'Variety')
        ->assertSeeIn('#season table tbody', 'Alphonso')
        ->assertSeeIn('#season table tbody', 'Chaunsa')
        ->assertSeeIn('#season table tbody', 'Nam Dok Mai');
});

it('renders the picking-guide tips', function () {
    visit('/')
        ->assertSee('How to pick a ripe one')
        ->assertSee('Squeeze gently')
        ->assertSee('Smell the stem end')
        ->assertSee('Look at the shoulders')
        ->assertSee('Skip the fridge');
});

it('hides the decorative hero cluster on a phone-sized viewport', function () {
    visit('/')
        ->on()->iPhone15Pro()
        ->assertSee('Mango Orchard')
        ->assertSee('Browse varieties')
        ->assertSee('Alphonso');
});

it('shows the decorative hero cluster on a desktop viewport', function () {
    visit('/')
        ->on()->desktop()
        ->assertSee('Mango Orchard')
        ->assertSee('Browse varieties');
});

it('loads without console errors or broken assets', function () {
    visit('/')
        ->assertNoJavaScriptErrors()
        ->assertNoMissingImages();
});
