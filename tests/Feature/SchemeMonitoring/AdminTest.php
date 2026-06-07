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

it('creates a designation with a parent (reports to)', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $parent = Designation::factory()->create(['name' => 'District Officer']);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.designations.store'), [
            'name' => 'Block Officer',
            'level' => 2,
            'parent_id' => $parent->id,
        ])
        ->assertRedirect();

    $child = Designation::where('name', 'Block Officer')->first();
    expect($child)->not->toBeNull();
    expect($child->parent_id)->toBe($parent->id);
});

it('rejects setting a designation as its own parent', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $d = Designation::factory()->create();

    $this->actingAs($admin)
        ->put(route('admin.monitoring.designations.update', $d), [
            'name' => $d->name,
            'level' => $d->level,
            'parent_id' => $d->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('rejects a designation cycle through a descendant', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $grandparent = Designation::factory()->create();
    $parent = Designation::factory()->create(['parent_id' => $grandparent->id]);
    $child = Designation::factory()->create(['parent_id' => $parent->id]);

    // grandparent cannot point to child — child is its descendant.
    $this->actingAs($admin)
        ->put(route('admin.monitoring.designations.update', $grandparent), [
            'name' => $grandparent->name,
            'level' => $grandparent->level,
            'parent_id' => $child->id,
        ])
        ->assertSessionHasErrors('parent_id');
});

it('updates a user\'s designations on the hierarchy admin page', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $user = User::factory()->monitor()->create();
    $d1 = Designation::factory()->create(['name' => 'Block Officer']);
    $d2 = Designation::factory()->create(['name' => 'District Officer']);

    $this->actingAs($admin)
        ->post(route('admin.monitoring.hierarchy.update', $user), [
            'designation_ids' => [$d1->id, $d2->id],
        ])
        ->assertRedirect();

    expect(MonitorProfile::where('user_id', $user->id)->exists())->toBeTrue();
    expect($user->fresh()->designations->pluck('id')->all())
        ->toEqualCanonicalizing([$d1->id, $d2->id]);
});

it('removes a user from the hierarchy', function () {
    $admin = User::factory()->monitorAdmin()->create();
    $user = User::factory()->monitor()->create();
    $d = Designation::factory()->create();
    MonitorProfile::create(['user_id' => $user->id]);
    $user->designations()->attach($d->id);

    $this->actingAs($admin)
        ->delete(route('admin.monitoring.hierarchy.destroy', $user))
        ->assertRedirect();

    expect(MonitorProfile::where('user_id', $user->id)->exists())->toBeFalse();
    expect($user->fresh()->designations)->toBeEmpty();
});
