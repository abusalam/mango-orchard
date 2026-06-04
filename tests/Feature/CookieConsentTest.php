<?php

declare(strict_types=1);

use App\Models\TelemetryEvent;
use App\Models\User;
use App\Telemetry\Telemetry;

// ============== Banner rendering ==============

it('renders the cookie banner on every layout for visitors', function () {
    // Public site (welcome) — guests.
    $this->get('/')
        ->assertOk()
        ->assertSee('data-testid="cookie-banner"', escape: false)
        ->assertSee('Cookies on Mango Orchard');

    // Guest layout (login) — also guests.
    $this->get(route('login'))
        ->assertOk()
        ->assertSee('data-testid="cookie-banner"', escape: false);

    // App / admin / onboarding layouts — authenticated users.
    $user = User::factory()->create();
    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertSee('data-testid="cookie-banner"', escape: false);

    $admin = User::factory()->superuser()->create();
    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('data-testid="cookie-banner"', escape: false);

    $unonboarded = User::factory()->unonboarded()->create();
    $this->actingAs($unonboarded)
        ->get('/onboarding/profile')
        ->assertOk()
        ->assertSee('data-testid="cookie-banner"', escape: false);
});

it('exposes both accept and necessary-only buttons in the banner', function () {
    $this->get('/')
        ->assertSee('data-testid="cookie-banner-accept"', escape: false)
        ->assertSee('data-testid="cookie-banner-necessary"', escape: false);
});

it('renders a reset cookie preferences control on the public footer', function () {
    $this->get('/')
        ->assertSee('data-testid="cookie-preferences-reset"', escape: false)
        ->assertSee('Cookie preferences');
});

// ============== Telemetry consent enforcement ==============

it('skips analytics events when the visitor has only consented to necessary cookies', function () {
    // tests/Pest.php defaults to consent=all; explicitly downgrade for this test.
    $this->unencryptedCookies = ['cookie_consent' => 'necessary'];

    $unonboarded = User::factory()->unonboarded()->create();
    $this->actingAs($unonboarded)
        ->post('/onboarding/profile', [
            'region' => 'Malda',
            'expertise' => 'enthusiast',
        ])->assertSessionHasNoErrors();

    expect(TelemetryEvent::where('event', Telemetry::ONBOARDING_PROFILE_SAVED)->count())->toBe(0);
});

it('records analytics events when the visitor has consented to all cookies', function () {
    $unonboarded = User::factory()->unonboarded()->create();

    $this->actingAs($unonboarded)
        ->withUnencryptedCookies(['cookie_consent' => 'all'])
        ->post('/onboarding/profile', [
            'region' => 'Malda',
            'expertise' => 'enthusiast',
        ])->assertSessionHasNoErrors();

    expect(TelemetryEvent::where('event', Telemetry::ONBOARDING_PROFILE_SAVED)->count())->toBe(1);
});

it('does NOT record analytics events when consent is "necessary"', function () {
    $unonboarded = User::factory()->unonboarded()->create();

    $this->actingAs($unonboarded)
        ->withUnencryptedCookies(['cookie_consent' => 'necessary'])
        ->post('/onboarding/profile', [
            'region' => 'Malda',
            'expertise' => 'enthusiast',
        ])->assertSessionHasNoErrors();

    expect(TelemetryEvent::where('event', Telemetry::ONBOARDING_PROFILE_SAVED)->count())->toBe(0);
});

// ============== Security events are always recorded ==============

it('records auth.login.succeeded even without analytics consent', function () {
    User::factory()->create([
        'email' => 'consent-test@example.com',
        'password' => bcrypt('login-pw-1234'),
    ]);

    $this->withUnencryptedCookies(['cookie_consent' => 'necessary'])
        ->post('/login', [
            'email' => 'consent-test@example.com',
            'password' => 'login-pw-1234',
        ])->assertRedirect('/dashboard');

    expect(TelemetryEvent::where('event', Telemetry::AUTH_LOGIN_SUCCEEDED)->count())->toBe(1);
});

it('records auth.login.failed even with necessary-only consent', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'necessary'];

    User::factory()->create([
        'email' => 'failtest@example.com',
        'password' => bcrypt('correct-pw-1234'),
    ]);

    $this->post('/login', [
        'email' => 'failtest@example.com',
        'password' => 'WRONG-password',
    ]);

    expect(TelemetryEvent::where('event', Telemetry::AUTH_LOGIN_FAILED)->count())->toBe(1);
});

it('records impersonation.started even without analytics consent', function () {
    $impersonator = User::factory()->impersonator()->create();
    $target = User::factory()->create();

    $this->actingAs($impersonator)
        ->withUnencryptedCookies(['cookie_consent' => 'necessary'])
        ->post("/admin/impersonate/users/{$target->id}");

    expect(TelemetryEvent::where('event', Telemetry::IMPERSONATION_STARTED)->count())->toBe(1);
});

// ============== Strict session cookie gating ==============

it('strips session + XSRF cookies from GET responses for un-consented guests', function () {
    $this->unencryptedCookies = [];

    $response = $this->get('/');
    $response->assertOk();

    $names = collect($response->headers->getCookies())->map->getName()->all();
    expect($names)->not->toContain((string) config('session.cookie'));
    expect($names)->not->toContain('XSRF-TOKEN');
});

it('strips cookies even on POST for un-consented guests (forces banner choice first)', function () {
    $this->unencryptedCookies = [];

    // POST /login without consent — CSRF will fail (no matching session),
    // but the key point: the response must NOT seed any cookies.
    $response = $this->post('/login', [
        'email' => 'whoever@example.com',
        'password' => 'whatever',
    ]);

    $names = collect($response->headers->getCookies())->map->getName()->all();
    expect($names)->not->toContain((string) config('session.cookie'));
    expect($names)->not->toContain('XSRF-TOKEN');
});

it('sets session + XSRF cookies once the visitor consents (necessary)', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'necessary'];

    $response = $this->get('/');
    $response->assertOk();

    $names = collect($response->headers->getCookies())->map->getName()->all();
    expect($names)->toContain((string) config('session.cookie'));
    expect($names)->toContain('XSRF-TOKEN');
});

it('sets session + XSRF cookies once the visitor consents (all)', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'all'];

    $response = $this->get('/');
    $response->assertOk();

    $names = collect($response->headers->getCookies())->map->getName()->all();
    expect($names)->toContain((string) config('session.cookie'));
    expect($names)->toContain('XSRF-TOKEN');
});

// ============== Feature access gating ==============

it('redirects an un-consented visitor away from gated features to the explainer', function () {
    $this->unencryptedCookies = [];

    $this->get('/dashboard')
        ->assertRedirect(route('cookies.required', ['return' => url('/dashboard')]));

    $this->get(route('login'))
        ->assertRedirect(route('cookies.required', ['return' => route('login')]));

    $this->get(route('register'))
        ->assertRedirect(route('cookies.required', ['return' => route('register')]));
});

it('redirects POST submissions from un-consented visitors to the explainer (no return param)', function () {
    $this->unencryptedCookies = [];

    $this->post('/login', [
        'email' => 'anyone@example.com',
        'password' => 'anything',
    ])->assertRedirect(route('cookies.required'));
});

it('still lets un-consented visitors browse public pages', function () {
    $this->unencryptedCookies = [];

    $this->get('/')->assertOk();
    $this->get(route('varieties.index'))->assertOk();
    $this->get(route('listings.index'))->assertOk();
    $this->get(route('events.index'))->assertOk();
    $this->get(route('advisories.index'))->assertOk();
});

it('shows the explainer page with the helpful info', function () {
    $this->unencryptedCookies = [];

    $this->get(route('cookies.required'))
        ->assertOk()
        ->assertSee('Cookies are needed for this')
        ->assertSee('data-testid="cookies-required-card"', escape: false)
        ->assertSee('Necessary only')
        ->assertSee('Accept all');
});

it('shows the return URL on the explainer when supplied', function () {
    $this->unencryptedCookies = [];

    $this->get(route('cookies.required', ['return' => url('/dashboard')]))
        ->assertOk()
        ->assertSee('data-testid="cookies-required-return"', escape: false)
        ->assertSee(url('/dashboard'));
});

it('ignores off-host return URLs to prevent open redirects', function () {
    $this->unencryptedCookies = [];

    $this->get(route('cookies.required', ['return' => 'https://evil.example.com/phish']))
        ->assertOk()
        ->assertDontSee('evil.example.com');
});

it('forwards the visitor to the return URL once consent is given', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'necessary'];

    $this->get(route('cookies.required', ['return' => url('/dashboard')]))
        ->assertRedirect(url('/dashboard'));
});

it('forwards consented visitors to home when no return URL is supplied', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'all'];

    $this->get(route('cookies.required'))
        ->assertRedirect(route('home'));
});

it('lets un-consented visitors hit logout (so they can sign out)', function () {
    // Logout is in the allowlist — once you click it, you should never be
    // stranded behind a banner. We just verify the middleware doesn't
    // bounce you to the explainer. The endpoint itself may 419 without a
    // CSRF token in this contrived state — that's fine, we're not asserting
    // a successful logout, just that the request reaches the controller.
    $this->unencryptedCookies = [];

    $response = $this->post(route('logout'));
    expect($response->getTargetUrl() ?? '')->not->toContain('cookies-required');
});

// ============== Direct API call ==============

it('Telemetry::record() respects consent for non-security events', function () {
    $this->unencryptedCookies = ['cookie_consent' => 'necessary'];
    $this->get('/'); // bind a request with the consent cookie attached

    $eventOff = app(Telemetry::class)->record(Telemetry::SETTINGS_UPDATED);
    expect($eventOff)->toBeNull();

    $eventSecurity = app(Telemetry::class)->record(Telemetry::AUTH_LOGOUT);
    expect($eventSecurity)->not->toBeNull();
});
