<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\Advisory;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Permissions;
use App\Roles;
use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

// ============== Permission + role scaffolding ==============

it('seeds the advisor role with the advisories.manage permission', function () {
    $role = Role::findByName(Roles::ADVISOR);
    expect($role->hasPermissionTo(Permissions::ADVISORIES_MANAGE))->toBeTrue();
});

it('grants the superuser the advisories.manage permission', function () {
    $superuser = User::factory()->superuser()->create();
    expect($superuser->can(Permissions::ADVISORIES_MANAGE))->toBeTrue();
});

it('does not grant advisories.manage to plain users or growers', function () {
    expect(User::factory()->create()->can(Permissions::ADVISORIES_MANAGE))->toBeFalse();
    expect(User::factory()->grower()->create()->can(Permissions::ADVISORIES_MANAGE))->toBeFalse();
});

it('lets users self-apply for the advisor role (it is in the delegatable + applicable set)', function () {
    expect(in_array(Roles::ADVISOR, Roles::delegatable(), true))->toBeTrue();
    expect(in_array(Roles::ADVISOR, Roles::nonApplicable(), true))->toBeFalse();
});

// ============== Public read ==============

it('shows the public /advisories index with only active published advisories', function () {
    Advisory::factory()->create(['title' => 'Visible Now']);
    Advisory::factory()->draft()->create(['title' => 'Hidden Draft']);
    Advisory::factory()->expired()->create(['title' => 'Expired Notice']);

    $this->get(route('advisories.index'))
        ->assertOk()
        ->assertSee('Visible Now')
        ->assertDontSee('Hidden Draft')
        ->assertDontSee('Expired Notice');
});

it('shows the public /advisories detail page for a published advisory', function () {
    $advisory = Advisory::factory()->urgent()->create([
        'title' => 'Pest pressure rising',
        'body' => 'Spray window opens this weekend.',
    ]);

    $this->get(route('advisories.show', $advisory))
        ->assertOk()
        ->assertSee('Pest pressure rising')
        ->assertSee('Spray window opens this weekend.')
        ->assertSee('Urgent');
});

it('returns 404 for a draft advisory when the viewer cannot manage advisories', function () {
    $advisory = Advisory::factory()->draft()->create();

    $this->get(route('advisories.show', $advisory))->assertNotFound();
});

it('lets an advisor preview a draft advisory via the public show route', function () {
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->draft()->create();

    $this->actingAs($advisor)
        ->get(route('advisories.show', $advisory))
        ->assertOk();
});

it('filters the public list by variety', function () {
    $alphonso = MangoVariety::factory()->create(['name' => 'Alphonso']);
    $chaunsa = MangoVariety::factory()->create(['name' => 'Chaunsa']);

    $alphonsoAdvisory = Advisory::factory()->create(['title' => 'Alphonso specific']);
    $alphonsoAdvisory->varieties()->attach($alphonso->id);

    $chaunsaAdvisory = Advisory::factory()->create(['title' => 'Chaunsa specific']);
    $chaunsaAdvisory->varieties()->attach($chaunsa->id);

    Advisory::factory()->create(['title' => 'General everyone']);

    $this->get(route('advisories.index', ['variety' => $alphonso->id]))
        ->assertOk()
        ->assertSee('Alphonso specific')
        ->assertDontSee('Chaunsa specific');
});

it('filters the public list by category', function () {
    Advisory::factory()->seasonal()->create(['title' => 'Seasonal one']);
    Advisory::factory()->bestPractice()->create(['title' => 'Best practice one']);
    Advisory::factory()->pestAlert()->create(['title' => 'Pest one']);

    $this->get(route('advisories.index', ['category' => Advisory::CATEGORY_PEST_ALERT]))
        ->assertOk()
        ->assertSee('Pest one')
        ->assertDontSee('Seasonal one')
        ->assertDontSee('Best practice one');
});

// ============== Admin CRUD ==============

it('lets an advisor reach the admin index + create form', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->get(route('admin.advisories.index'))
        ->assertOk()
        ->assertSee('Orchard advisories');

    $this->actingAs($advisor)
        ->get(route('admin.advisories.create'))
        ->assertOk()
        ->assertSee('Issue an advisory');
});

it('forbids non-advisors from the admin index', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('admin.advisories.index'))->assertForbidden();
});

it('creates a published advisory targeting specific varieties', function () {
    $advisor = User::factory()->advisor()->create();
    $alphonso = MangoVariety::factory()->create();
    $kesar = MangoVariety::factory()->create();

    $this->actingAs($advisor)
        ->post(route('admin.advisories.store'), [
            'title' => 'Pre-monsoon pruning window',
            'body' => 'Begin pruning 3 weeks before flowering.',
            'category' => Advisory::CATEGORY_BEST_PRACTICE,
            'severity' => Advisory::SEVERITY_INFO,
            'mango_variety_ids' => [$alphonso->id, $kesar->id],
            'published' => '1',
        ])
        ->assertRedirect(route('admin.advisories.index'))
        ->assertSessionHasNoErrors();

    $advisory = Advisory::firstWhere('title', 'Pre-monsoon pruning window');
    expect($advisory)->not->toBeNull();
    expect($advisory->published)->toBeTrue();
    expect($advisory->issued_at)->not->toBeNull(); // stamped at publish time
    expect($advisory->issued_by)->toBe($advisor->id);
    expect($advisory->varieties->pluck('id')->all())->toEqualCanonicalizing([$alphonso->id, $kesar->id]);
});

it('creates a draft advisory with no varieties (general)', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->post(route('admin.advisories.store'), [
            'title' => 'Draft note',
            'body' => 'Sketch.',
            'category' => Advisory::CATEGORY_SEASONAL,
            'severity' => Advisory::SEVERITY_INFO,
            'mango_variety_ids' => [],
            'published' => '0',
        ])
        ->assertSessionHasNoErrors();

    $advisory = Advisory::firstWhere('title', 'Draft note');
    expect($advisory->published)->toBeFalse();
    expect($advisory->isGeneral())->toBeTrue();
});

it('rejects an advisory with an invalid category or severity', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->from(route('admin.advisories.create'))
        ->post(route('admin.advisories.store'), [
            'title' => 'X',
            'body' => 'Y',
            'category' => 'made-up-category',
            'severity' => 'made-up-severity',
        ])
        ->assertSessionHasErrors(['category', 'severity']);
});

it('rejects expires_at before issued_at', function () {
    $advisor = User::factory()->advisor()->create();

    $this->actingAs($advisor)
        ->from(route('admin.advisories.create'))
        ->post(route('admin.advisories.store'), [
            'title' => 'X',
            'body' => 'Y',
            'category' => Advisory::CATEGORY_SEASONAL,
            'severity' => Advisory::SEVERITY_INFO,
            'issued_at' => now()->toDateTimeString(),
            'expires_at' => now()->subDay()->toDateTimeString(),
        ])
        ->assertSessionHasErrors('expires_at');
});

it('updates an advisory and re-syncs varieties', function () {
    $advisor = User::factory()->advisor()->create();
    $alphonso = MangoVariety::factory()->create();
    $kesar = MangoVariety::factory()->create();
    $advisory = Advisory::factory()->create();
    $advisory->varieties()->attach($alphonso->id);

    $this->actingAs($advisor)
        ->put(route('admin.advisories.update', $advisory), [
            'title' => 'Edited',
            'body' => $advisory->body,
            'category' => $advisory->category,
            'severity' => Advisory::SEVERITY_WARNING,
            'mango_variety_ids' => [$kesar->id],
            'published' => '1',
        ])
        ->assertRedirect(route('admin.advisories.index'))
        ->assertSessionHasNoErrors();

    $advisory->refresh();
    expect($advisory->title)->toBe('Edited');
    expect($advisory->severity)->toBe(Advisory::SEVERITY_WARNING);
    expect($advisory->varieties->pluck('id')->all())->toBe([$kesar->id]);
});

it('deletes an advisory', function () {
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->create();

    $this->actingAs($advisor)
        ->delete(route('admin.advisories.destroy', $advisory))
        ->assertRedirect(route('admin.advisories.index'));

    expect(Advisory::find($advisory->id))->toBeNull();
});

// ============== Telemetry ==============

it('records telemetry events on create / update / delete', function () {
    $advisor = User::factory()->advisor()->create();
    $advisory = Advisory::factory()->create(['issued_by' => $advisor->id]);

    expect(TelemetryEvent::where('event', Telemetry::ADVISORY_CREATED)->exists())->toBeTrue();

    $advisory->update(['severity' => Advisory::SEVERITY_URGENT]);
    expect(TelemetryEvent::where('event', Telemetry::ADVISORY_UPDATED)->exists())->toBeTrue();

    $advisory->delete();
    expect(TelemetryEvent::where('event', Telemetry::ADVISORY_DELETED)->exists())->toBeTrue();
});

// ============== Variety detail integration ==============

it('renders advisories targeting a variety on /varieties/{variety}', function () {
    $variety = MangoVariety::factory()->create(['name' => 'Targeted Variety']);
    $matching = Advisory::factory()->urgent()->create(['title' => 'Matching advisory']);
    $matching->varieties()->attach($variety->id);

    $other = MangoVariety::factory()->create();
    $offTarget = Advisory::factory()->create(['title' => 'Off-target advisory']);
    $offTarget->varieties()->attach($other->id);

    $general = Advisory::factory()->create(['title' => 'General advisory']);

    $this->get(route('varieties.show', $variety))
        ->assertOk()
        ->assertSee('Matching advisory')
        ->assertSee('General advisory')
        ->assertDontSee('Off-target advisory');
});

// ============== Dashboard integration ==============

it('surfaces active advisories on the dashboard for any onboarded user', function () {
    $user = User::factory()->create();
    Advisory::factory()->urgent()->create(['title' => 'Urgent dashboard advisory']);
    Advisory::factory()->draft()->create(['title' => 'Hidden draft']);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Active advisories')
        ->assertSee('Urgent dashboard advisory')
        ->assertSee('dashboard-advisories', escape: false)
        ->assertDontSee('Hidden draft');
});

it('prioritises advisories matching the growers listings, with general advisories included', function () {
    $alphonso = MangoVariety::factory()->create(['name' => 'Alphonso']);
    $kesar = MangoVariety::factory()->create(['name' => 'Kesar']);
    $grower = User::factory()->grower()->create();
    Listing::factory()->create(['user_id' => $grower->id, 'mango_variety_id' => $alphonso->id]);

    // Targeting Alphonso → should show.
    $relevant = Advisory::factory()->create(['title' => 'Alphonso relevant']);
    $relevant->varieties()->attach($alphonso->id);

    // Targeting Kesar only → should NOT show (grower doesn't grow Kesar).
    $irrelevant = Advisory::factory()->create(['title' => 'Kesar only']);
    $irrelevant->varieties()->attach($kesar->id);

    // General advisory → should show.
    Advisory::factory()->create(['title' => 'General orchard tip']);

    $this->actingAs($grower)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('Alphonso relevant')
        ->assertSee('General orchard tip')
        ->assertDontSee('Kesar only');
});

it('does not show the dashboard advisories section when there are no active advisories', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertDontSee('dashboard-advisories', escape: false);
});
