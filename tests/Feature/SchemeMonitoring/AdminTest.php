<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;

it('creates a designation', function () {
    $admin = User::factory()->monitorAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.designations.store'), [
            'name' => 'Block Officer',
            'level' => 2,
            'description' => 'Field-level reporting.',
        ])
        ->assertRedirect();

    expect(Designation::where('name', 'Block Officer')->exists())->toBeTrue();
});

it('rejects a duplicate designation name', function () {
    $admin = User::factory()->monitorAdmin()->create();
    Designation::factory()->create(['name' => 'District Officer']);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.designations.store'), [
            'name' => 'District Officer',
            'level' => 5,
        ])
        ->assertSessionHasErrors('name');
});

it('updates a user\'s monitoring profile (parent + designations)', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $parent = User::factory()->monitor()->create();
    $child = User::factory()->monitor()->create();
    $d1 = Designation::factory()->create(['name' => 'Block Officer']);
    $d2 = Designation::factory()->create(['name' => 'District Officer']);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.hierarchy.update', $child), [
            'parent_user_id' => $parent->id,
            'designation_ids' => [$d1->id, $d2->id],
        ])
        ->assertRedirect();

    $profile = MonitorProfile::where('user_id', $child->id)->first();
    expect($profile)->not->toBeNull();
    expect($profile->parent_user_id)->toBe($parent->id);
    expect($child->fresh()->designations->pluck('id')->all())
        ->toEqualCanonicalizing([$d1->id, $d2->id]);
});

it('rejects assigning a user as their own parent', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $user = User::factory()->monitor()->create();

    $this->actingAs($admin)
        ->post(route('admin.monitoring.hierarchy.update', $user), [
            'parent_user_id' => $user->id,
        ])
        ->assertSessionHasErrors('parent_user_id');
});

it('removes a user from the hierarchy', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $user = User::factory()->monitor()->create();
    $d = Designation::factory()->create();
    MonitorProfile::create(['user_id' => $user->id, 'parent_user_id' => null]);
    $user->designations()->attach($d->id);

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.hierarchy.destroy', $user))
        ->assertRedirect();

    expect(MonitorProfile::where('user_id', $user->id)->exists())->toBeFalse();
    expect($user->fresh()->designations)->toBeEmpty();
});
