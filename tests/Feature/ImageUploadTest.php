<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\Advisory;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;
use App\Roles;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(fn () => Storage::fake('public'));

// ============== Listing image upload ==============

it('stores an uploaded image when a grower creates a listing', function () {
    $grower = User::factory()->grower()->create();
    $variety = MangoVariety::factory()->create();

    $this->actingAs($grower)->post(route('my.listings.store'), [
        'mango_variety_id' => $variety->id,
        'farm_name' => 'Image Farm',
        'location' => 'Malda',
        'description' => 'Sample',
        'availability_start_month' => 4,
        'availability_end_month' => 6,
        'price_per_kg' => '450.00',
        'quantity_available_kg' => 500,
        'contact_email' => 'farm@example.com',
        'status' => Listing::STATUS_PUBLISHED,
        'image' => UploadedFile::fake()->image('harvest.jpg', 1200, 800),
    ])->assertSessionHasNoErrors()->assertRedirect(route('my.listings.index'));

    $listing = Listing::firstWhere('farm_name', 'Image Farm');
    expect($listing->image_path)->not->toBeNull();
    expect($listing->image_path)->toStartWith('listings/');
    Storage::disk('public')->assertExists($listing->image_path);
});

it('rejects a non-image upload on a listing', function () {
    $grower = User::factory()->grower()->create();
    $variety = MangoVariety::factory()->create();

    $this->actingAs($grower)
        ->from(route('my.listings.create'))
        ->post(route('my.listings.store'), [
            'mango_variety_id' => $variety->id,
            'farm_name' => 'X',
            'location' => 'Y',
            'availability_start_month' => 4,
            'availability_end_month' => 6,
            'status' => Listing::STATUS_DRAFT,
            'image' => UploadedFile::fake()->create('virus.exe', 100),
        ])
        ->assertSessionHasErrors('image');
});

it('rejects an image larger than the 5MB limit on a listing', function () {
    $grower = User::factory()->grower()->create();
    $variety = MangoVariety::factory()->create();

    $this->actingAs($grower)
        ->from(route('my.listings.create'))
        ->post(route('my.listings.store'), [
            'mango_variety_id' => $variety->id,
            'farm_name' => 'X',
            'location' => 'Y',
            'availability_start_month' => 4,
            'availability_end_month' => 6,
            'status' => Listing::STATUS_DRAFT,
            'image' => UploadedFile::fake()->image('huge.jpg')->size(6 * 1024),
        ])
        ->assertSessionHasErrors('image');
});

it('replaces the image on update and deletes the old file', function () {
    Storage::fake('public');
    $grower = User::factory()->grower()->create();
    $listing = Listing::factory()->create([
        'user_id' => $grower->id,
        'image_path' => UploadedFile::fake()->image('old.jpg')->store('listings', 'public'),
    ]);
    $oldPath = $listing->image_path;
    Storage::disk('public')->assertExists($oldPath);

    $this->actingAs($grower)->put(route('my.listings.update', $listing), [
        'mango_variety_id' => $listing->mango_variety_id,
        'farm_name' => $listing->farm_name,
        'location' => $listing->location,
        'availability_start_month' => $listing->availability_start_month,
        'availability_end_month' => $listing->availability_end_month,
        'status' => $listing->status,
        'image' => UploadedFile::fake()->image('new.jpg'),
    ])->assertSessionHasNoErrors();

    $listing->refresh();
    expect($listing->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($listing->image_path);
});

it('removes the listing image when remove_image is checked', function () {
    Storage::fake('public');
    $grower = User::factory()->grower()->create();
    $listing = Listing::factory()->create([
        'user_id' => $grower->id,
        'image_path' => UploadedFile::fake()->image('keep.jpg')->store('listings', 'public'),
    ]);
    $path = $listing->image_path;

    $this->actingAs($grower)->put(route('my.listings.update', $listing), [
        'mango_variety_id' => $listing->mango_variety_id,
        'farm_name' => $listing->farm_name,
        'location' => $listing->location,
        'availability_start_month' => $listing->availability_start_month,
        'availability_end_month' => $listing->availability_end_month,
        'status' => $listing->status,
        'remove_image' => '1',
    ])->assertSessionHasNoErrors();

    expect($listing->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('deletes the file from the public disk when the listing is destroyed', function () {
    Storage::fake('public');
    $grower = User::factory()->grower()->create();
    $listing = Listing::factory()->create([
        'user_id' => $grower->id,
        'image_path' => UploadedFile::fake()->image('bye.jpg')->store('listings', 'public'),
    ]);
    $path = $listing->image_path;

    $this->actingAs($grower)->delete(route('my.listings.destroy', $listing));

    Storage::disk('public')->assertMissing($path);
});

it('renders the listing image on the public marketplace card and detail page', function () {
    Storage::fake('public');
    $listing = Listing::factory()->create([
        'farm_name' => 'Photo Farm',
        'image_path' => UploadedFile::fake()->image('hero.jpg')->store('listings', 'public'),
    ]);

    $this->get(route('listings.index'))
        ->assertOk()
        ->assertSee('data-testid="listing-card-image"', escape: false)
        ->assertSee('listings/');

    $this->get(route('listings.show', $listing))
        ->assertOk()
        ->assertSee('data-testid="listing-show-image"', escape: false);
});

// ============== Advisory image upload ==============

it('stores an uploaded image when an advisor creates an advisory', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)->post(route('admin.advisories.store'), [
        'title' => 'Hopper alert',
        'body' => 'Body',
        'category' => Advisory::CATEGORY_PEST_ALERT,
        'severity' => Advisory::SEVERITY_URGENT,
        'published' => '1',
        'image' => UploadedFile::fake()->image('hopper.jpg', 800, 600),
    ])->assertSessionHasNoErrors()->assertRedirect(route('admin.advisories.index'));

    $advisory = Advisory::firstWhere('title', 'Hopper alert');
    expect($advisory->image_path)->not->toBeNull();
    expect($advisory->image_path)->toStartWith('advisories/');
    Storage::disk('public')->assertExists($advisory->image_path);
});

it('rejects a non-image upload on an advisory', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->from(route('admin.advisories.create'))
        ->post(route('admin.advisories.store'), [
            'title' => 'X',
            'body' => 'Y',
            'category' => Advisory::CATEGORY_SEASONAL,
            'severity' => Advisory::SEVERITY_INFO,
            'image' => UploadedFile::fake()->create('virus.exe', 100),
        ])
        ->assertSessionHasErrors('image');
});

it('replaces the advisory image on update', function () {
    Storage::fake('public');
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->create([
        'image_path' => UploadedFile::fake()->image('old.jpg')->store('advisories', 'public'),
    ]);
    $oldPath = $advisory->image_path;

    $this->actingAs($advisor)->put(route('admin.advisories.update', $advisory), [
        'title' => $advisory->title,
        'body' => $advisory->body,
        'category' => $advisory->category,
        'severity' => $advisory->severity,
        'published' => '1',
        'image' => UploadedFile::fake()->image('new.jpg'),
    ])->assertSessionHasNoErrors();

    $advisory->refresh();
    expect($advisory->image_path)->not->toBe($oldPath);
    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($advisory->image_path);
});

it('removes the advisory image when remove_image is checked', function () {
    Storage::fake('public');
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->create([
        'image_path' => UploadedFile::fake()->image('keep.jpg')->store('advisories', 'public'),
    ]);
    $path = $advisory->image_path;

    $this->actingAs($advisor)->put(route('admin.advisories.update', $advisory), [
        'title' => $advisory->title,
        'body' => $advisory->body,
        'category' => $advisory->category,
        'severity' => $advisory->severity,
        'published' => '1',
        'remove_image' => '1',
    ])->assertSessionHasNoErrors();

    expect($advisory->fresh()->image_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});

it('deletes the advisory file when the advisory is destroyed', function () {
    Storage::fake('public');
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->create([
        'image_path' => UploadedFile::fake()->image('bye.jpg')->store('advisories', 'public'),
    ]);
    $path = $advisory->image_path;

    $this->actingAs($advisor)->delete(route('admin.advisories.destroy', $advisory));

    Storage::disk('public')->assertMissing($path);
});

it('renders the advisory image on the public list and detail page', function () {
    Storage::fake('public');
    $advisory = Advisory::factory()->create([
        'title' => 'Photo advisory',
        'image_path' => UploadedFile::fake()->image('hero.jpg')->store('advisories', 'public'),
    ]);

    $this->get(route('advisories.index'))
        ->assertOk()
        ->assertSee('data-testid="advisory-card-image"', escape: false);

    $this->get(route('advisories.show', $advisory))
        ->assertOk()
        ->assertSee('data-testid="advisory-show-image"', escape: false);
});

// ============== Forms expose the upload control ==============

it('renders an image file input on the listing create form', function () {
    $grower = User::factory()->grower()->create();

    $this->actingAs($grower)
        ->get(route('my.listings.create'))
        ->assertOk()
        ->assertSee('data-testid="listing-image-input"', escape: false)
        ->assertSee('Harvest photo');
});

it('renders an image file input on the advisory create form', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->get(route('admin.advisories.create'))
        ->assertOk()
        ->assertSee('data-testid="advisory-image-input"', escape: false)
        ->assertSee('Illustrative photo');
});
