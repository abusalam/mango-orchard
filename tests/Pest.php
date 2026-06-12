<?php

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use App\Settings\Settings;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Pest\Browser\Playwright\Playwright;
use Tests\TestCase;

// Bump Playwright's default 5s assertion timeout. Full-suite browser runs
// share a single in-process dev server, and individual page loads can spike
// past 5s under load — surfacing as `Timeout 5000ms exceeded` on assertions
// that pass comfortably when the same test runs in isolation.
Playwright::setTimeout(15_000);

// The cookie banner is suppressed in browser tests via a `HeadlessChrome`
// User-Agent check in resources/views/components/cookie-banner.blade.php.
// The banner JS reads `document.cookie`, which Playwright's fresh context
// never has, and the fixed-position banner would otherwise overlay the
// bottom of every page and intercept clicks on Save buttons.

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Telemetry now gates analytics events behind a `cookie_consent=all` cookie.
// Most existing tests assert "telemetry was recorded" without thinking
// about consent — default the test suite to "accepted" so those keep
// passing. Tests that exercise the no-consent path (CookieConsentTest)
// override per-test with their own withUnencryptedCookies(…) or by
// resetting `$this->unencryptedCookies` directly.
//
// Two surfaces need consent:
//   1. HTTP requests built through the test client → withUnencryptedCookies
//   2. Direct Telemetry::record() / model-observer calls (no HTTP) →
//      bind the cookie onto the singleton request the IoC has resolved
$bootstrapTest = function (): void {
    $this->seed(RolePermissionSeeder::class);
    $this->withUnencryptedCookies(['cookie_consent' => 'all']);
    $this->app['request']->cookies->set('cookie_consent', 'all');

    // RefreshDatabase leaves the users table empty, which would trip the
    // first-run /setup redirect (RequireSiteSetup) on every guest request.
    // Mark setup complete by default; setup-flow tests reset this flag.
    app(Settings::class)->set(Settings::SITE_SETUP_COMPLETED, true);
};

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach($bootstrapTest)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->beforeEach($bootstrapTest)
    ->in('Browser');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function something()
{
    // ..
}

/**
 * Helper for tests that need a monitoring-hierarchy of users. Reporting
 * structure now lives on the designation tree, so a "parent" relation
 * between two users requires creating two designations (parent + child)
 * and tagging each user with theirs.
 *
 * Accepts a list of [User, ?User] pairs — the first element is the user,
 * the second is their reporting parent (or null for top of tree). Pairs
 * are processed in order, so a parent must appear before its children.
 *
 * Wires up:
 *   - A MonitorProfile row per user (enrolment).
 *   - One Designation per user, with parent_id pointing at the parent
 *     user's designation. Designation names are namespaced per-call so
 *     concurrent tests don't collide on the unique index.
 *   - The user → designation pivot via monitoring_user_designations.
 *
 * @param  array<int, array{0: User, 1: ?User}>  $pairs
 */
function monitorHierarchy(array $pairs): void
{
    $designationByUserId = [];
    $suffix = bin2hex(random_bytes(4));

    foreach ($pairs as $i => [$user, $parent]) {
        $parentDesignationId = null;
        if ($parent !== null) {
            $parentDesignationId = $designationByUserId[$parent->id]
                ?? throw new RuntimeException("monitorHierarchy: parent user {$parent->id} not yet wired — list parents before their children");
        }

        $designation = Designation::create([
            'name' => "Test-{$suffix}-{$i}-{$user->id}",
            'level' => 0,
            'parent_id' => $parentDesignationId,
        ]);

        $user->designations()->attach($designation->id);
        MonitorProfile::firstOrCreate(['user_id' => $user->id]);

        $designationByUserId[$user->id] = $designation->id;
    }
}
