<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Modules\MangoOrchard\Notifications\VarietyInSeason;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

afterEach(function () {
    Carbon::setTestNow(null);
});

it('does nothing on a day that is not the first of a month', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::create(2026, 5, 14, 6, 30));
    User::factory()->create(['notify_seasonal' => true, 'email_verified_at' => now()]);
    MangoVariety::factory()->create(['season_start' => 5]);

    $this->artisan('mango:dispatch-seasonal-alerts')
        ->expectsOutputToContain('Not the first of the month')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('sends to opted-in verified subscribers when a variety enters its season month', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 6, 30));

    $alphonso = MangoVariety::factory()->create(['name' => 'Alphonso', 'season_start' => 5, 'season_end' => 7]);
    $kesar = MangoVariety::factory()->create(['name' => 'Kesar', 'season_start' => 6]); // not yet

    $subscriber = User::factory()->create(['notify_seasonal' => true, 'email_verified_at' => now()]);
    User::factory()->create(['notify_seasonal' => false, 'email_verified_at' => now()]); // opted out
    User::factory()->create(['notify_seasonal' => true, 'email_verified_at' => null]); // unverified

    $this->artisan('mango:dispatch-seasonal-alerts')->assertExitCode(0);

    Notification::assertSentTo($subscriber, VarietyInSeason::class, function (VarietyInSeason $n) use ($alphonso) {
        return $n->variety->is($alphonso);
    });
    Notification::assertSentTimes(VarietyInSeason::class, 1); // only Alphonso, not Kesar
});

it('dry-run prints the plan without dispatching', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 6, 30));
    MangoVariety::factory()->create(['name' => 'Alphonso', 'season_start' => 5]);
    User::factory()->create(['notify_seasonal' => true, 'email_verified_at' => now()]);

    $this->artisan('mango:dispatch-seasonal-alerts', ['--dry-run' => true])
        ->expectsOutputToContain('[dry-run] Alphonso')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('handles the case where no varieties start this month gracefully', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::create(2026, 5, 1, 6, 30));
    User::factory()->create(['notify_seasonal' => true, 'email_verified_at' => now()]);
    // No varieties whose season_start == 5.
    MangoVariety::factory()->create(['season_start' => 6]);

    $this->artisan('mango:dispatch-seasonal-alerts')
        ->expectsOutputToContain('No varieties starting season')
        ->assertExitCode(0);

    Notification::assertNothingSent();
});

it('renders the variety_in_season email template with sample data', function () {
    $variety = MangoVariety::factory()->create([
        'name' => 'Alphonso',
        'origin' => 'Ratnagiri',
        'season_start' => 5,
        'season_end' => 7,
    ]);

    $notification = new VarietyInSeason($variety);
    $mail = $notification->toMail((object) ['name' => 'Test User']);

    expect($mail->subject)->toBe('Alphonso is in season');
    $body = implode("\n", $mail->introLines);
    expect($body)
        ->toContain('Alphonso')
        ->toContain('Ratnagiri')
        ->toContain('May to July');
});
