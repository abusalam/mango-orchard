<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Telemetry\Telemetry;

function validListingAttributes(array $overrides = []): array
{
    return array_merge([
        'mango_variety_id' => MangoVariety::factory()->create()->id,
        'farm_name' => 'Sunrise Orchards',
        'location' => 'Ratnagiri, Maharashtra',
        'description' => 'Family-run Alphonso grove for 40 years.',
        'availability_start_month' => 4,
        'availability_end_month' => 6,
        'price_per_kg' => 450.00,
        'quantity_available_kg' => 500,
        'contact_email' => 'orders@sunrise.example',
        'contact_phone' => '+91 98765 43210',
        'status' => Listing::STATUS_PUBLISHED,
    ], $overrides);
}

// ============== Public marketplace ==============

it('shows the public marketplace with published listings', function () {
    Listing::factory()->create(['farm_name' => 'Visible Farm']);
    Listing::factory()->draft()->create(['farm_name' => 'Hidden Draft']);

    $this->get(route('listings.index'))
        ->assertOk()
        ->assertSee('Visible Farm')
        ->assertDontSee('Hidden Draft');
});

it('shows sold-out listings on the marketplace (with the sold-out tag)', function () {
    Listing::factory()->soldOut()->create(['farm_name' => 'Done Farm']);

    $this->get(route('listings.index'))
        ->assertOk()
        ->assertSee('Done Farm')
        ->assertSee('Sold out');
});

it('filters marketplace listings by variety', function () {
    $alphonso = MangoVariety::factory()->create(['name' => 'Alphonso']);
    $kesar = MangoVariety::factory()->create(['name' => 'Kesar']);

    Listing::factory()->create(['farm_name' => 'Alphonso Farm', 'mango_variety_id' => $alphonso->id]);
    Listing::factory()->create(['farm_name' => 'Kesar Farm', 'mango_variety_id' => $kesar->id]);

    $this->get(route('listings.index', ['variety' => $alphonso->id]))
        ->assertSee('Alphonso Farm')
        ->assertDontSee('Kesar Farm');
});

it('returns 404 for a draft listing accessed by anyone other than its owner', function () {
    $listing = Listing::factory()->draft()->create();

    $this->get(route('listings.show', $listing))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('listings.show', $listing))->assertNotFound();
});

it('shows the public detail of a published listing', function () {
    $listing = Listing::factory()->create([
        'farm_name' => 'Coastline Orchards',
        'contact_email' => 'farm@example.com',
    ]);

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('Coastline Orchards')
        ->assertSee('farm@example.com');
});

it('lets the owner preview their own draft listing', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->draft()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee($listing->farm_name);
});

// ============== Owner CRUD ==============

it('redirects guests away from /my/listings', function () {
    $this->get(route('my.listings.index'))->assertRedirect(route('login'));
    $this->get(route('my.listings.create'))->assertRedirect(route('login'));
    $this->post(route('my.listings.store'), [])->assertRedirect(route('login'));
});

it('lists only the current user\'s listings on /my/listings', function () {
    $alice = User::factory()->create();
    $bob = User::factory()->create();

    Listing::factory()->create(['user_id' => $alice->id, 'farm_name' => 'Alice Farm']);
    Listing::factory()->create(['user_id' => $bob->id, 'farm_name' => 'Bob Farm']);

    $this->actingAs($alice)->get(route('my.listings.index'))
        ->assertOk()
        ->assertSee('Alice Farm')
        ->assertDontSee('Bob Farm');
});

it('creates a listing for the authenticated grower', function () {
    $owner = User::factory()->grower()->create();
    $variety = MangoVariety::factory()->create();

    $payload = validListingAttributes(['mango_variety_id' => $variety->id, 'farm_name' => 'Brand New Farm']);

    $this->actingAs($owner)
        ->post(route('my.listings.store'), $payload)
        ->assertRedirect(route('my.listings.index'));

    $listing = Listing::firstWhere('farm_name', 'Brand New Farm');
    expect($listing)->not->toBeNull()
        ->and($listing->user_id)->toBe($owner->id)
        ->and($listing->mango_variety_id)->toBe($variety->id);
});

it('validates required fields on store', function () {
    $this->actingAs(User::factory()->grower()->create())
        ->post(route('my.listings.store'), [])
        ->assertSessionHasErrors(['mango_variety_id', 'farm_name', 'location', 'availability_start_month', 'availability_end_month', 'status']);

    expect(Listing::count())->toBe(0);
});

it('rejects an availability end month before the start month', function () {
    $this->actingAs(User::factory()->grower()->create())
        ->post(route('my.listings.store'), validListingAttributes([
            'availability_start_month' => 8,
            'availability_end_month' => 4,
        ]))
        ->assertSessionHasErrors('availability_end_month');
});

it('rejects an unknown status', function () {
    $this->actingAs(User::factory()->grower()->create())
        ->post(route('my.listings.store'), validListingAttributes(['status' => 'pending-approval']))
        ->assertSessionHasErrors('status');
});

it('updates own listing', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->put(route('my.listings.update', $listing), validListingAttributes([
            'mango_variety_id' => $listing->mango_variety_id,
            'farm_name' => 'Renamed Farm',
        ]))
        ->assertRedirect();

    expect($listing->fresh()->farm_name)->toBe('Renamed Farm');
});

it("blocks updating another user's listing", function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id, 'farm_name' => 'Hands Off']);

    $this->actingAs($other)
        ->put(route('my.listings.update', $listing), validListingAttributes())
        ->assertForbidden();

    expect($listing->fresh()->farm_name)->toBe('Hands Off');
});

it('lets a superuser update any listing', function () {
    $owner = User::factory()->create();
    $admin = User::factory()->superuser()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($admin)
        ->put(route('my.listings.update', $listing), validListingAttributes([
            'mango_variety_id' => $listing->mango_variety_id,
            'farm_name' => 'Admin Renamed',
        ]))
        ->assertRedirect();

    expect($listing->fresh()->farm_name)->toBe('Admin Renamed');
});

it('deletes own listing', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($owner)
        ->delete(route('my.listings.destroy', $listing))
        ->assertRedirect();

    expect(Listing::whereKey($listing->id)->exists())->toBeFalse();
});

it("blocks deleting another user's listing", function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->delete(route('my.listings.destroy', $listing))
        ->assertForbidden();

    expect(Listing::whereKey($listing->id)->exists())->toBeTrue();
});

// ============== Telemetry ==============

it('records listing.created on store', function () {
    $owner = User::factory()->grower()->create();

    $this->actingAs($owner)
        ->post(route('my.listings.store'), validListingAttributes(['farm_name' => 'Telemetry Farm']));

    $event = TelemetryEvent::where('event', Telemetry::LISTING_CREATED)->latest('id')->first();
    expect($event)->not->toBeNull()
        ->and($event->context['farm'])->toBe('Telemetry Farm');
});

it('records listing.updated only when fields actually change', function () {
    $owner = User::factory()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);
    $beforeCount = TelemetryEvent::where('event', Telemetry::LISTING_UPDATED)->count();

    $listing->update(['farm_name' => $listing->farm_name]); // no-op
    expect(TelemetryEvent::where('event', Telemetry::LISTING_UPDATED)->count())->toBe($beforeCount);

    $listing->update(['farm_name' => 'Telemetry Updated']);
    $event = TelemetryEvent::where('event', Telemetry::LISTING_UPDATED)->latest('id')->first();
    expect($event)->not->toBeNull()
        ->and($event->context['changed'])->toContain('farm_name');
});

it('records listing.deleted on destroy', function () {
    $listing = Listing::factory()->create(['farm_name' => 'Going Away']);
    $listing->delete();

    $event = TelemetryEvent::where('event', Telemetry::LISTING_DELETED)->latest('id')->first();
    expect($event)->not->toBeNull()
        ->and($event->context['farm'])->toBe('Going Away');
});

// ============== Onboarding gate interactions ==============

it('lets unonboarded users browse the public marketplace', function () {
    $user = User::factory()->unonboarded()->create();
    Listing::factory()->create(['farm_name' => 'Public Farm']);

    $this->actingAs($user)->get(route('listings.index'))->assertOk()->assertSee('Public Farm');
});

it('forces unonboarded users to finish onboarding before reaching /my/listings', function () {
    $user = User::factory()->unonboarded()->create();

    $this->actingAs($user)->get(route('my.listings.index'))
        ->assertRedirect(route('onboarding.start'));
});

// ============== Side nav ==============

it('exposes the My listings menu item to growers', function () {
    $grower = User::factory()->grower()->create();

    $this->actingAs($grower)->get(route('varieties.index'))->assertSee('My listings');
});

it('hides the My listings menu item from non-growers', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('varieties.index'))->assertDontSee('My listings');
});

// ============== Create-gate (listings.manage permission) ==============

it('blocks authed users without listings.manage from the create form', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('my.listings.create'))
        ->assertForbidden();
});

it('blocks authed users without listings.manage from the store endpoint', function () {
    $this->actingAs(User::factory()->create())
        ->post(route('my.listings.store'), validListingAttributes())
        ->assertForbidden();

    expect(Listing::count())->toBe(0);
});

it('lets a user with the grower role view the create form', function () {
    $this->actingAs(User::factory()->grower()->create())
        ->get(route('my.listings.create'))
        ->assertOk()
        ->assertSee('List a mango variety');
});

it('lets a superuser create listings (inherits listings.manage)', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->post(route('my.listings.store'), validListingAttributes(['farm_name' => 'Admin Farm']))
        ->assertRedirect(route('my.listings.index'));

    expect(Listing::where('farm_name', 'Admin Farm')->exists())->toBeTrue();
});

it('still lets a former grower edit their own existing listings even without the permission', function () {
    // Simulate: user had grower role, created a listing, then lost the role.
    $owner = User::factory()->grower()->create();
    $listing = Listing::factory()->create(['user_id' => $owner->id]);
    $owner->removeRole(Roles::GROWER);

    // Update is ownership-based, not permission-based — so this still works.
    $this->actingAs($owner)
        ->put(route('my.listings.update', $listing), validListingAttributes([
            'mango_variety_id' => $listing->mango_variety_id,
            'farm_name' => 'Demoted But Still Mine',
        ]))
        ->assertRedirect();

    expect($listing->fresh()->farm_name)->toBe('Demoted But Still Mine');
});

it('hides "List your harvest" CTA from authed users without listings.manage', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('listings.index'))
        ->assertSee('grower')
        ->assertDontSee('List your own harvest');
});

it('shows "List your harvest" CTA to growers on the public marketplace', function () {
    $user = User::factory()->grower()->create();

    $this->actingAs($user)->get(route('listings.index'))
        ->assertSee('List your own harvest');
});

it('shows a helpful empty-state on /my/listings to a non-grower with no listings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('my.listings.index'))
        ->assertOk()
        ->assertSee('grower')
        ->assertDontSee('New listing'); // the header CTA is hidden
});
