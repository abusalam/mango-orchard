<?php

declare(strict_types=1);

use App\Models\User;
use App\Permissions;
use Spatie\Permission\Models\Role;

/**
 * Enforces the project-wide rule: no link rendered in the UI should ever lead
 * to a 403. For each persona, fetch every page that might render nav/dropdown
 * links, scrape the hrefs, and follow each one — anything 403'ing fails.
 */
function pagesToScrape(): array
{
    return ['/', '/varieties', '/listings', '/dashboard', '/my/listings', '/admin'];
}

/**
 * Pull every same-origin href out of an HTML response body.
 */
function hrefsFrom(string $html): array
{
    preg_match_all('/<a\b[^>]*\shref="([^"#]+)"/i', $html, $m);

    return collect($m[1])
        ->reject(fn ($href) => str_starts_with($href, 'http')
            || str_starts_with($href, 'mailto:')
            || str_starts_with($href, 'tel:')
            || str_starts_with($href, '#')
        )
        ->map(fn ($href) => str_starts_with($href, '/') ? $href : '/'.$href)
        ->unique()
        ->values()
        ->all();
}

/**
 * @return array<string, list<string>> persona name → list of permissions
 */
function personas(): array
{
    return [
        'guest' => [],
        'plain-auth' => [],
        'grower' => [Permissions::LISTINGS_MANAGE],
        'curator' => [Permissions::VARIETIES_MANAGE],
        'settings-only-admin' => [Permissions::SETTINGS_MANAGE],
        'telemetry-only-admin' => [Permissions::TELEMETRY_VIEW],
        'roles-only-admin' => [Permissions::ROLES_MANAGE],
        'users-only-admin' => [Permissions::USERS_MANAGE],
        'superuser' => array_keys(Permissions::ALL),
    ];
}

foreach (personas() as $persona => $permissions) {
    it("renders no link that 403s for persona [{$persona}]", function () use ($persona, $permissions) {
        $user = null;
        if ($persona !== 'guest') {
            $role = Role::findOrCreate("test-{$persona}", 'web');
            $role->syncPermissions($permissions);
            $user = User::factory()->create();
            $user->assignRole($role);
        }

        $request = $user ? $this->actingAs($user) : $this;

        foreach (pagesToScrape() as $page) {
            $response = $request->get($page);

            // Skip pages this persona shouldn't even land on — we only care
            // about links *rendered* by reachable pages.
            if (in_array($response->status(), [302, 403, 404], true)) {
                continue;
            }

            $response->assertOk();

            foreach (hrefsFrom($response->getContent()) as $href) {
                $follow = $user ? $this->actingAs($user)->get($href) : $this->get($href);

                expect($follow->status())->not->toBe(
                    403,
                    "Persona [{$persona}] saw link {$href} on {$page}, but following it returns 403."
                );
            }
        }
    });
}

it('admin.home redirects each persona with any admin permission to a page they can actually reach', function () {
    $adminPermissions = [
        Permissions::USERS_MANAGE => '/admin/users',
        Permissions::ROLES_MANAGE => '/admin/roles',
        Permissions::SETTINGS_MANAGE => '/admin/settings',
        Permissions::TELEMETRY_VIEW => '/admin/telemetry',
    ];

    foreach ($adminPermissions as $permission => $expectedPath) {
        $role = Role::findOrCreate("only-{$permission}", 'web');
        $role->syncPermissions([$permission]);
        $user = User::factory()->create();
        $user->assignRole($role);

        $this->actingAs($user)
            ->get('/admin')
            ->assertRedirect($expectedPath);

        $this->actingAs($user)
            ->get($expectedPath)
            ->assertOk();
    }
});

it('admin.home 403s when an authenticated user has no admin permission at all', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin')
        ->assertForbidden();
});
