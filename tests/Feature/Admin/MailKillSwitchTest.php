<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MangoOrchard\Models\NewsletterIssue;
use App\Modules\MangoOrchard\Notifications\NewsletterIssued;
use App\Modules\MangoOrchard\Notifications\VarietyInSeason;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskStatusChanged;
use App\Settings\Settings;

beforeEach(function () {
    app(Settings::class)->forget();
});

// ============== Settings helpers ==============

it('defaults mail flags to ON', function () {
    $s = app(Settings::class);
    expect($s->mailEnabled())->toBeTrue();
    expect($s->mailEnabledForMangoOrchard())->toBeTrue();
    expect($s->mailEnabledForSchemeMonitoring())->toBeTrue();
});

it('flipping the master switch off masks the per-module switches', function () {
    $s = app(Settings::class);
    $s->set(Settings::MAIL_ENABLED, false);
    $s->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, true);
    $s->set(Settings::MAIL_SCHEME_MONITORING_ENABLED, true);

    expect($s->mailEnabled())->toBeFalse();
    expect($s->mailEnabledForMangoOrchard())->toBeFalse();
    expect($s->mailEnabledForSchemeMonitoring())->toBeFalse();
});

it('per-module switch off only affects its module', function () {
    $s = app(Settings::class);
    $s->set(Settings::MAIL_ENABLED, true);
    $s->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, false);
    $s->set(Settings::MAIL_SCHEME_MONITORING_ENABLED, true);

    expect($s->mailEnabledForMangoOrchard())->toBeFalse();
    expect($s->mailEnabledForSchemeMonitoring())->toBeTrue();
});

// ============== Notification via() gating ==============

it('TaskStatusChanged drops the mail channel when Pragati Darpan mail is off', function () {
    $task = Task::factory()->create();
    $notif = new TaskStatusChanged($task, 'pending', 'completed');

    app(Settings::class)->set(Settings::MAIL_SCHEME_MONITORING_ENABLED, false);
    expect($notif->via())->toBe(['database']);

    app(Settings::class)->set(Settings::MAIL_SCHEME_MONITORING_ENABLED, true);
    expect($notif->via())->toBe(['mail', 'database']);
});

it('VarietyInSeason drops the mail channel when Mango Orchard mail is off', function () {
    $variety = \App\Modules\MangoOrchard\Models\MangoVariety::factory()->create();
    $notif = new VarietyInSeason($variety);

    app(Settings::class)->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, false);
    expect($notif->via())->toBe(['database']);

    app(Settings::class)->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, true);
    expect($notif->via())->toBe(['mail', 'database']);
});

it('NewsletterIssued returns an empty channel list when Mango Orchard mail is off', function () {
    $issue = NewsletterIssue::create([
        'subject' => 'x',
        'body' => 'x',
        'created_by' => null,
    ]);
    $notif = new NewsletterIssued($issue);

    app(Settings::class)->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, false);
    expect($notif->via())->toBe([]);

    app(Settings::class)->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, true);
    expect($notif->via())->toBe(['mail']);
});

// ============== Newsletter send refusal ==============

it('newsletter send refuses when Mango Orchard mail is disabled', function () {
    $curator = User::factory()->curator()->create();
    User::factory()->create(['subscribe_newsletter' => true, 'email_verified_at' => now()]);

    $issue = NewsletterIssue::create([
        'subject' => 'Hi',
        'body' => 'x',
        'created_by' => $curator->id,
    ]);

    app(Settings::class)->set(Settings::MAIL_MANGO_ORCHARD_ENABLED, false);

    $this->actingAs($curator)
        ->post(route('admin.mango-orchard.newsletter.send', $issue))
        ->assertRedirect()
        ->assertSessionHasErrors('send');

    expect($issue->fresh()->sent_at)->toBeNull();
});

// ============== Settings page UI ==============

it('shows the mail-delivery fieldset to a superuser', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('data-testid="mail-fieldset"', escape: false)
        ->assertSee('data-testid="mail-enabled-toggle"', escape: false)
        ->assertSee('data-testid="mail-mango-orchard-toggle"', escape: false)
        ->assertSee('data-testid="mail-scheme-monitoring-toggle"', escape: false);
});

it('persists the mail toggles via the Settings form', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->put(route('admin.settings.update'), [
            'mail_enabled' => '0',
            'mail_mango_orchard_enabled' => '1',
            'mail_scheme_monitoring_enabled' => '0',
        ])
        ->assertRedirect();

    app(Settings::class)->forget();
    $s = app(Settings::class);
    expect($s->mailEnabled())->toBeFalse();
    // Mango Orchard checkbox was on, but the master is off → masked.
    expect($s->mailEnabledForMangoOrchard())->toBeFalse();
    expect($s->mailEnabledForSchemeMonitoring())->toBeFalse();
});
