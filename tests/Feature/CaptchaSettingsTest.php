<?php

declare(strict_types=1);

use App\Captcha\Captcha;
use App\Models\User;
use App\Settings\Settings;
use Illuminate\Support\Facades\Cache;
use Mews\Captcha\Captcha as MewsCaptcha;

beforeEach(function () {
    app(Settings::class)->forget();
});

function enableCaptcha(bool $autosolve = false): void
{
    $settings = app(Settings::class);
    $settings->set(Settings::CAPTCHA_ENABLED, true);
    $settings->set(Settings::CAPTCHA_AUTOSOLVE, $autosolve);
}

/**
 * Generate a captcha (storing the hash in session) and return the plaintext
 * the user would have to type. Mirrors what mews/captcha's image route does
 * server-side; lets us submit a "correct" answer in a feature test.
 */
function freshCaptchaAnswer(): string
{
    $result = app(MewsCaptcha::class)->create('default', api: true);
    $cached = Cache::get('captcha_'.md5($result['key']));

    return is_array($cached) ? implode('', $cached) : (string) $cached;
}

it('defaults to captcha disabled and autosolve disabled', function () {
    expect(app(Settings::class)->captchaEnabled())->toBeFalse()
        ->and(app(Settings::class)->captchaAutosolve())->toBeFalse();

    $this->get(route('login'))
        ->assertOk()
        ->assertDontSee('data-testid="captcha-field"', false);

    $this->get(route('register'))
        ->assertOk()
        ->assertDontSee('data-testid="captcha-field"', false);
});

it('renders the captcha image (via URL) on login and register when enabled', function () {
    enableCaptcha();

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-testid="captcha-field"', false)
        ->assertSee('data-testid="captcha-image"', false)
        ->assertSee('/captcha/default', false)
        ->assertDontSee('data-testid="captcha-autosolve-hint"', false);
});

it('renders the autosolve hint and prefills the input when autosolve is on', function () {
    enableCaptcha(autosolve: true);

    $response = $this->get(route('login'));

    $response->assertSee('data-testid="captcha-autosolve-hint"', false)
        ->assertSee('prefilled with the correct answer')
        // Inline data URI image instead of mews's /captcha/default URL:
        ->assertSee('src="data:image/jpeg;base64', false);

    // The prefilled value is whatever the captcha just generated in session.
    $html = $response->getContent();
    expect($html)->toMatch('/<input[^>]*name="captcha"[^>]*value="[^"]+"/');
});

it('rejects login with a wrong captcha answer when captcha is enforced', function () {
    enableCaptcha();
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);
    freshCaptchaAnswer(); // populate session

    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
        Captcha::FIELD => 'definitely-not-the-answer',
    ])->assertSessionHasErrors(Captcha::FIELD);

    $this->assertGuest();
});

it('rejects login when captcha is enabled and the answer is missing', function () {
    enableCaptcha();
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
    ])->assertSessionHasErrors(Captcha::FIELD);

    $this->assertGuest();
});

it('accepts login with the correct captcha answer', function () {
    enableCaptcha();
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $answer = freshCaptchaAnswer();

    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
        Captcha::FIELD => $answer,
    ])->assertRedirect();

    $this->assertAuthenticated();
});

it('autosolve still validates server-side — wrong override fails', function () {
    enableCaptcha(autosolve: true);
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Render the form so the autosolve flow generates & stores a captcha.
    $this->get(route('login'));

    // User (or bot) overrides the prefilled value with junk → still rejected.
    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
        Captcha::FIELD => 'tampered-value',
    ])->assertSessionHasErrors(Captcha::FIELD);

    $this->assertGuest();
});

it('autosolve makes login pass when the prefilled value is submitted as-is', function () {
    enableCaptcha(autosolve: true);
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    // Simulate "open the page" — captcha is generated, session has the hash,
    // payload exposes the plaintext that a real browser would prefill into the input.
    $payload = app(Captcha::class)->imagePayload();
    $prefilled = $payload['prefill'];

    expect($prefilled)->not->toBeNull();

    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
        Captcha::FIELD => $prefilled,
    ])->assertRedirect();

    $this->assertAuthenticated();
});

it('does not require captcha on login when disabled', function () {
    User::factory()->create([
        'email' => 'real@example.com',
        'password' => bcrypt('correct-password'),
    ]);

    $this->post(route('login'), [
        'email' => 'real@example.com',
        'password' => 'correct-password',
    ])->assertRedirect();

    $this->assertAuthenticated();
});

it('rejects registration when captcha is enforced and answer is wrong', function () {
    enableCaptcha();
    freshCaptchaAnswer();

    $this->post(route('register'), [
        'name' => 'Bot User',
        'email' => 'bot@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
        Captcha::FIELD => 'wrong',
    ])->assertSessionHasErrors(Captcha::FIELD);

    expect(User::where('email', 'bot@example.com')->exists())->toBeFalse();
    $this->assertGuest();
});

it('accepts registration when captcha answer is the correct prefilled value', function () {
    enableCaptcha(autosolve: true);
    $payload = app(Captcha::class)->imagePayload();

    $this->post(route('register'), [
        'name' => 'Real Person',
        'email' => 'real-person@example.com',
        'password' => 'a-strong-password',
        'password_confirmation' => 'a-strong-password',
        Captcha::FIELD => $payload['prefill'],
    ])->assertRedirect(route('onboarding.start'));

    expect(User::where('email', 'real-person@example.com')->exists())->toBeTrue();
});

it('treats autosolve as a no-op when captcha itself is disabled', function () {
    app(Settings::class)->set(Settings::CAPTCHA_AUTOSOLVE, true);

    expect(app(Settings::class)->captchaAutosolve())->toBeFalse();
});

it('payload prefill is null when autosolve is off, populated when on', function () {
    enableCaptcha();
    $offPayload = app(Captcha::class)->imagePayload();
    expect($offPayload['prefill'])->toBeNull()
        ->and($offPayload['src'])->toContain('/captcha/default');

    enableCaptcha(autosolve: true);
    $onPayload = app(Captcha::class)->imagePayload();
    expect($onPayload['prefill'])->toBeString()
        ->and($onPayload['prefill'])->not->toBeEmpty()
        ->and($onPayload['src'])->toStartWith('data:image/jpeg;base64,');
});

it('blocks the admin settings page from users without settings.manage', function () {
    $this->actingAs(User::factory()->curator()->create())
        ->get(route('admin.settings.edit'))
        ->assertForbidden();
});

it('blocks settings updates from users without settings.manage', function () {
    $this->actingAs(User::factory()->curator()->create())
        ->put(route('admin.settings.update'), ['captcha_enabled' => '1'])
        ->assertForbidden();

    expect(app(Settings::class)->captchaEnabled())->toBeFalse();
});

it('lets a superuser see the settings page with both toggles', function () {
    enableCaptcha();

    $this->actingAs(User::factory()->superuser()->create())
        ->get(route('admin.settings.edit'))
        ->assertOk()
        ->assertSee('Captcha')
        ->assertSee('Autosolve captcha')
        ->assertSee('data-testid="captcha-toggle"', false)
        ->assertSee('data-testid="autosolve-toggle"', false);
});

it('lets a superuser toggle captcha on through the form', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->put(route('admin.settings.update'), ['captcha_enabled' => '1'])
        ->assertRedirect(route('admin.settings.edit'))
        ->assertSessionHas('status');

    expect(app(Settings::class)->captchaEnabled())->toBeTrue();
});

it('lets a superuser toggle both captcha and autosolve on through the form', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->put(route('admin.settings.update'), [
            'captcha_enabled' => '1',
            'captcha_autosolve' => '1',
        ])->assertRedirect(route('admin.settings.edit'));

    expect(app(Settings::class)->captchaEnabled())->toBeTrue()
        ->and(app(Settings::class)->captchaAutosolve())->toBeTrue();
});

it('lets a superuser toggle both off through the form', function () {
    enableCaptcha(autosolve: true);

    $this->actingAs(User::factory()->superuser()->create())
        ->put(route('admin.settings.update'), [])
        ->assertRedirect(route('admin.settings.edit'));

    expect(app(Settings::class)->captchaEnabled())->toBeFalse()
        ->and(app(Settings::class)->captchaAutosolve())->toBeFalse();
});

it('shows the Settings sidebar link to superusers but not curators', function () {
    $this->actingAs(User::factory()->superuser()->create())
        ->get(route('admin.users.index'))
        ->assertSee('Settings');

    $this->actingAs(User::factory()->curator()->create())
        ->get(route('varieties.index'))
        ->assertDontSee(route('admin.settings.edit'), false);
});
