<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Scheme;

it('lets a monitor create a scheme they own', function () {
    $monitor = User::factory()->monitor()->create();

    $response = $this->actingAs($monitor)
        ->post(route('monitoring.schemes.store'), [
            'name' => 'Drinking Water Programme',
            'description' => 'District-wide rollout.',
            'start_date' => '2026-07-01',
            'end_date' => '2026-12-31',
            'status' => 'active',
        ]);

    $scheme = Scheme::where('name', 'Drinking Water Programme')->first();
    expect($scheme)->not->toBeNull()->owner_id->toBe($monitor->id);

    // After creating, redirect to the new scheme's edit page so the
    // creator can immediately attach files / fill in extras.
    $response->assertRedirect(route('monitoring.schemes.edit', $scheme));
});

it('rejects an end_date before start_date', function () {
    $monitor = User::factory()->monitor()->create();

    $this->actingAs($monitor)
        ->post(route('monitoring.schemes.store'), [
            'name' => 'Bad scheme',
            'start_date' => '2026-12-31',
            'end_date' => '2026-07-01',
            'status' => 'active',
        ])
        ->assertSessionHasErrors('end_date');
});

it('blocks updating someone else\'s scheme', function () {
    $owner = User::factory()->monitor()->create();
    $other = User::factory()->monitor()->create();
    $scheme = Scheme::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($other)
        ->put(route('monitoring.schemes.update', $scheme), [
            'name' => 'Hijacked',
            'status' => 'active',
        ])
        ->assertForbidden();
});

it('lets a monitor-admin update any scheme', function () {
    $owner = User::factory()->monitor()->create();
    $admin = User::factory()->monitorAdmin()->create();
    $scheme = Scheme::factory()->create(['owner_id' => $owner->id]);

    $this->actingAs($admin)
        ->put(route('monitoring.schemes.update', $scheme), [
            'name' => 'Admin-renamed',
            'status' => 'paused',
        ])
        ->assertRedirect();

    expect($scheme->fresh()->name)->toBe('Admin-renamed');
});
