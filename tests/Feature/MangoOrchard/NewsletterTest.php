<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MangoOrchard\Models\NewsletterIssue;
use App\Modules\MangoOrchard\Notifications\NewsletterIssued;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

// ============== Page gating ==============

it('blocks a regular user from the newsletter admin', function () {
    $this->actingAs(User::factory()->create())
        ->get(route('admin.mango-orchard.newsletter.index'))
        ->assertForbidden();
});

it('lets a curator open the newsletter admin', function () {
    $curator = User::factory()->curator()->create();

    $this->actingAs($curator)
        ->get(route('admin.mango-orchard.newsletter.index'))
        ->assertOk()
        ->assertSee('Orchard newsletter')
        ->assertSee('data-testid="newsletter-subscriber-count"', escape: false);
});

it('shows the subscribed-and-verified subscriber count on the index', function () {
    $curator = User::factory()->curator()->create();
    // Two subscribers + verified — included.
    User::factory()->count(2)->create(['subscribe_newsletter' => true, 'email_verified_at' => now()]);
    // Subscribed but unverified — excluded.
    User::factory()->create(['subscribe_newsletter' => true, 'email_verified_at' => null]);
    // Verified but not subscribed — excluded.
    User::factory()->create(['subscribe_newsletter' => false, 'email_verified_at' => now()]);

    $this->actingAs($curator)
        ->get(route('admin.mango-orchard.newsletter.index'))
        ->assertOk()
        // The curator themselves is verified + not_subscribe by default,
        // so the count is exactly the 2 we explicitly subscribed.
        ->assertSee('<span class="font-medium text-stone-900 dark:text-stone-100">2</span> subscribers', escape: false);
});

// ============== Compose / draft CRUD ==============

it('creates a draft', function () {
    $curator = User::factory()->curator()->create();

    $this->actingAs($curator)
        ->post(route('admin.mango-orchard.newsletter.store'), [
            'subject' => 'June orchard notes',
            'body' => 'Hello growers.',
        ])
        ->assertRedirect();

    $draft = NewsletterIssue::first();
    expect($draft)->not->toBeNull();
    expect($draft->isDraft())->toBeTrue();
    expect($draft->created_by)->toBe($curator->id);
});

it('blocks editing a sent issue', function () {
    $curator = User::factory()->curator()->create();
    $issue = NewsletterIssue::create([
        'subject' => 'Already sent',
        'body' => 'x',
        'sent_at' => now(),
        'sent_to_count' => 5,
        'created_by' => $curator->id,
    ]);

    $this->actingAs($curator)
        ->get(route('admin.mango-orchard.newsletter.edit', $issue))
        ->assertForbidden();
});

it('blocks deleting a sent issue (kept for audit)', function () {
    $curator = User::factory()->curator()->create();
    $issue = NewsletterIssue::create([
        'subject' => 'Already sent',
        'body' => 'x',
        'sent_at' => now(),
        'sent_to_count' => 5,
        'created_by' => $curator->id,
    ]);

    $this->actingAs($curator)
        ->delete(route('admin.mango-orchard.newsletter.destroy', $issue))
        ->assertForbidden();

    expect(NewsletterIssue::find($issue->id))->not->toBeNull();
});

// ============== Send flow ==============

it('queues NewsletterIssued to subscribers and stamps sent_at + count', function () {
    Notification::fake();
    $curator = User::factory()->curator()->create();
    $subscribed = User::factory()->count(3)->create(['subscribe_newsletter' => true, 'email_verified_at' => now()]);
    User::factory()->create(['subscribe_newsletter' => false, 'email_verified_at' => now()]); // excluded

    $issue = NewsletterIssue::create([
        'subject' => 'June orchard notes',
        'body' => 'A line.',
        'created_by' => $curator->id,
    ]);

    $this->actingAs($curator)
        ->post(route('admin.mango-orchard.newsletter.send', $issue))
        ->assertRedirect(route('admin.mango-orchard.newsletter.index'));

    Notification::assertSentTo($subscribed, NewsletterIssued::class);

    $fresh = $issue->fresh();
    expect($fresh->sent_at)->not->toBeNull();
    expect($fresh->sent_to_count)->toBe(3);
});

it('refuses to send when there are no opted-in subscribers', function () {
    Notification::fake();
    $curator = User::factory()->curator()->create();

    $issue = NewsletterIssue::create([
        'subject' => 'Empty audience',
        'body' => 'A line.',
        'created_by' => $curator->id,
    ]);

    $this->actingAs($curator)
        ->post(route('admin.mango-orchard.newsletter.send', $issue))
        ->assertRedirect()
        ->assertSessionHasErrors('send');

    Notification::assertNothingSent();
    expect($issue->fresh()->sent_at)->toBeNull();
});

it('refuses to re-send an already-sent issue', function () {
    Notification::fake();
    $curator = User::factory()->curator()->create();
    $issue = NewsletterIssue::create([
        'subject' => 'Done',
        'body' => 'x',
        'sent_at' => now(),
        'sent_to_count' => 1,
        'created_by' => $curator->id,
    ]);

    $this->actingAs($curator)
        ->post(route('admin.mango-orchard.newsletter.send', $issue))
        ->assertStatus(409);
});

// ============== Unsubscribe flow ==============

it('flips subscribe_newsletter off on a valid signed unsubscribe', function () {
    $user = User::factory()->create(['subscribe_newsletter' => true]);
    $url = URL::signedRoute('preferences.unsubscribe-newsletter', ['user' => $user->id]);

    $this->withUnencryptedCookies(['cookie_consent' => 'all'])
        ->get($url)
        ->assertOk()
        // Apostrophe is HTML-escaped to &#039; in the rendered page, so
        // bypass assertSee's default escaping by skipping the apostrophe.
        ->assertSee('unsubscribed')
        ->assertSee($user->email);

    expect($user->fresh()->subscribe_newsletter)->toBeFalse();
});

it('rejects an unsigned (tampered) unsubscribe URL', function () {
    $user = User::factory()->create(['subscribe_newsletter' => true]);

    $this->withUnencryptedCookies(['cookie_consent' => 'all'])
        ->get(route('preferences.unsubscribe-newsletter', ['user' => $user->id]))
        ->assertStatus(403);

    // Unchanged.
    expect($user->fresh()->subscribe_newsletter)->toBeTrue();
});

it('is idempotent — visiting unsubscribe twice does not error', function () {
    $user = User::factory()->create(['subscribe_newsletter' => true]);
    $url = URL::signedRoute('preferences.unsubscribe-newsletter', ['user' => $user->id]);

    $this->withUnencryptedCookies(['cookie_consent' => 'all'])->get($url)->assertOk();
    $this->withUnencryptedCookies(['cookie_consent' => 'all'])->get($url)->assertOk();

    expect($user->fresh()->subscribe_newsletter)->toBeFalse();
});
