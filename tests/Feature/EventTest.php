<?php

declare(strict_types=1);

use App\Modules\MangoOrchard\Models\Event;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Telemetry\Telemetry;

function validEventAttributes(array $overrides = []): array
{
    return array_merge([
        'title' => 'Pruning Workshop',
        'description' => 'A hands-on field day covering canopy management.',
        'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
        'end_at' => now()->addWeek()->addHours(3)->format('Y-m-d H:i:s'),
        'location' => 'Ratnagiri, Maharashtra',
        'location_url' => null,
        'host' => 'KVK Ratnagiri',
        'capacity' => 40,
        'registration_url' => null,
        'status' => Event::STATUS_PUBLISHED,
    ], $overrides);
}

// ============== Public listing ==============

it('shows the public events index with published, cancelled and completed events but not drafts', function () {
    Event::factory()->create(['title' => 'Visible Upcoming']);
    Event::factory()->cancelled()->create(['title' => 'Cancelled Event']);
    Event::factory()->completed()->create(['title' => 'Past Completed']);
    Event::factory()->draft()->create(['title' => 'Hidden Draft']);

    $this->get(route('events.index'))
        ->assertOk()
        ->assertSee('Visible Upcoming')
        ->assertSee('Cancelled Event')
        ->assertSee('Past Completed')
        ->assertDontSee('Hidden Draft');
});

it('returns 404 for a draft event accessed by anyone without manage permission', function () {
    $event = Event::factory()->draft()->create();

    $this->get(route('events.show', $event))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('events.show', $event))->assertNotFound();
});

it('lets a convener preview a draft event', function () {
    $manager = User::factory()->convener()->create();
    $event = Event::factory()->draft()->create(['title' => 'Secret Draft']);

    $this->actingAs($manager)->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Secret Draft');
});

it('shows the public detail of a published event', function () {
    $event = Event::factory()->create([
        'title' => 'Export Standards Webinar',
        'host' => 'APEDA',
    ]);

    $this->get(route('events.show', $event))
        ->assertOk()
        ->assertSee('Export Standards Webinar')
        ->assertSee('APEDA');
});

it('shows the external registration button only for published, future events with a URL', function () {
    $registerable = Event::factory()->create([
        'title' => 'Open For Signup',
        'registration_url' => 'https://example.com/register',
    ]);
    $this->get(route('events.show', $registerable))
        ->assertSee('https://example.com/register');

    $past = Event::factory()->completed()->create([
        'title' => 'Already Done',
        'registration_url' => 'https://example.com/register-past',
    ]);
    $this->get(route('events.show', $past))
        ->assertDontSee('https://example.com/register-past');
});

// ============== Admin CRUD permission gating ==============

it('blocks guests from admin events CRUD', function () {
    $this->get(route('admin.events.index'))->assertRedirect(route('login'));
    $this->get(route('admin.events.create'))->assertRedirect(route('login'));
    $this->post(route('admin.events.store'), [])->assertRedirect(route('login'));
});

it('blocks authed users without events.manage from admin events', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.events.index'))->assertForbidden();
    $this->actingAs($user)->get(route('admin.events.create'))->assertForbidden();
    $this->actingAs($user)->post(route('admin.events.store'), validEventAttributes())->assertForbidden();
});

it('blocks growers from admin events (different role)', function () {
    $grower = User::factory()->grower()->create();

    $this->actingAs($grower)->get(route('admin.events.index'))->assertForbidden();
});

it('lets a convener reach the admin events index and create form', function () {
    $manager = User::factory()->convener()->create();

    $this->actingAs($manager)->get(route('admin.events.index'))->assertOk();
    $this->actingAs($manager)->get(route('admin.events.create'))->assertOk();
});

it('lets a superuser CRUD events (inherits events.manage)', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->post(route('admin.events.store'), validEventAttributes(['title' => 'Admin-created Event']))
        ->assertRedirect(route('admin.events.index'));

    expect(Event::where('title', 'Admin-created Event')->exists())->toBeTrue();
});

it('creates an event for a convener', function () {
    $manager = User::factory()->convener()->create();

    $this->actingAs($manager)
        ->post(route('admin.events.store'), validEventAttributes(['title' => 'Brand New Workshop']))
        ->assertRedirect(route('admin.events.index'));

    $event = Event::firstWhere('title', 'Brand New Workshop');
    expect($event)->not->toBeNull()
        ->and($event->slug)->toBe('brand-new-workshop');
});

it('validates required fields on store', function () {
    $this->actingAs(User::factory()->convener()->create())
        ->post(route('admin.events.store'), [])
        ->assertSessionHasErrors(['title', 'description', 'start_at', 'location', 'status']);

    expect(Event::count())->toBe(0);
});

it('rejects an end_at before start_at', function () {
    $this->actingAs(User::factory()->convener()->create())
        ->post(route('admin.events.store'), validEventAttributes([
            'start_at' => now()->addWeek()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDay()->format('Y-m-d H:i:s'),
        ]))
        ->assertSessionHasErrors('end_at');
});

it('rejects an unknown status', function () {
    $this->actingAs(User::factory()->convener()->create())
        ->post(route('admin.events.store'), validEventAttributes(['status' => 'pending-approval']))
        ->assertSessionHasErrors('status');
});

it('updates an event', function () {
    $manager = User::factory()->convener()->create();
    $event = Event::factory()->create(['title' => 'Original Title']);

    $this->actingAs($manager)
        ->put(route('admin.events.update', $event), validEventAttributes(['title' => 'Renamed Event']))
        ->assertRedirect();

    expect($event->fresh()->title)->toBe('Renamed Event');
});

it('deletes an event', function () {
    $manager = User::factory()->convener()->create();
    $event = Event::factory()->create();

    $this->actingAs($manager)
        ->delete(route('admin.events.destroy', $event))
        ->assertRedirect(route('admin.events.index'));

    expect(Event::whereKey($event->id)->exists())->toBeFalse();
});

// ============== Slug auto-fill ==============

it('auto-fills the slug from the title when blank', function () {
    $event = Event::factory()->create(['title' => 'Soil Health Clinic']);

    expect($event->slug)->toBe('soil-health-clinic');
});

it('disambiguates duplicate slugs', function () {
    Event::factory()->create(['title' => 'Pruning Workshop']);
    $second = Event::factory()->create(['title' => 'Pruning Workshop']);

    expect($second->slug)->toBe('pruning-workshop-2');
});

it('preserves a manually provided slug', function () {
    $event = Event::factory()->create(['title' => 'Anything', 'slug' => 'custom-slug']);

    expect($event->slug)->toBe('custom-slug');
});

// ============== Telemetry ==============

it('records event.created on store', function () {
    $this->actingAs(User::factory()->convener()->create())
        ->post(route('admin.events.store'), validEventAttributes(['title' => 'Telemetry Event']));

    $event = TelemetryEvent::where('event', Telemetry::EVENT_CREATED)->latest('id')->first();
    expect($event)->not->toBeNull()
        ->and($event->context['title'])->toBe('Telemetry Event');
});

it('records event.updated only when fields actually change', function () {
    $event = Event::factory()->create();
    $before = TelemetryEvent::where('event', Telemetry::EVENT_UPDATED)->count();

    $event->update(['title' => $event->title]); // no-op
    expect(TelemetryEvent::where('event', Telemetry::EVENT_UPDATED)->count())->toBe($before);

    $event->update(['title' => 'Telemetry Updated']);
    $record = TelemetryEvent::where('event', Telemetry::EVENT_UPDATED)->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->context['changed'])->toContain('title');
});

it('records event.deleted on destroy', function () {
    $event = Event::factory()->create(['title' => 'Going Away']);
    $event->delete();

    $record = TelemetryEvent::where('event', Telemetry::EVENT_DELETED)->latest('id')->first();
    expect($record)->not->toBeNull()
        ->and($record->context['title'])->toBe('Going Away');
});

// ============== Onboarding gate ==============

it('lets unonboarded users browse the public events page', function () {
    $user = User::factory()->unonboarded()->create();
    Event::factory()->create(['title' => 'Public Event']);

    $this->actingAs($user)->get(route('events.index'))->assertOk()->assertSee('Public Event');
});

it('forces unonboarded users to finish onboarding before reaching admin events', function () {
    $manager = User::factory()->unonboarded()->create();
    $manager->assignRole(Roles::CONVENER);

    $this->actingAs($manager)->get(route('admin.events.index'))
        ->assertRedirect(route('onboarding.start'));
});

// ============== Admin home redirect for event-only managers ==============

it('redirects admin.home to admin.events.index for a convener with no other admin permission', function () {
    $manager = User::factory()->convener()->create();

    $this->actingAs($manager)->get('/admin')->assertRedirect(route('admin.events.index'));
});
