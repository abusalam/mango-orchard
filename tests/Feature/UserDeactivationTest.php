<?php

declare(strict_types=1);

use App\Models\User;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Permissions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

it('lets an admin deactivate and reactivate a user', function () {
    $admin = User::factory()->superuser()->create();
    $user = User::factory()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $user))
        ->assertRedirect();

    expect($user->fresh()->isDeactivated())->toBeTrue();

    $this->actingAs($admin)
        ->patch(route('admin.users.reactivate', $user))
        ->assertRedirect();

    expect($user->fresh()->isDeactivated())->toBeFalse();
});

it('blocks deactivating your own account', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->patch(route('admin.users.deactivate', $admin))
        ->assertSessionHasErrors('user');

    expect($admin->fresh()->isDeactivated())->toBeFalse();
});

it('rejects login for deactivated users without leaking enumeration', function () {
    $user = User::factory()->create(['password' => 'Secret123!']);
    $user->forceFill(['deactivated_at' => now()])->save();

    $this->post('/login', ['email' => $user->email, 'password' => 'Secret123!'])
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

it('kicks an active session when the account is deactivated mid-session', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();

    $user->forceFill(['deactivated_at' => now()])->save();

    $this->get('/dashboard')->assertRedirect(route('login'));
    $this->assertGuest();
});

it('lets an admin delete a user and cleans up their listings', function () {
    $admin = User::factory()->superuser()->create();
    $grower = User::factory()->grower()->create();
    $variety = MangoVariety::factory()->create();
    $listing = Listing::factory()->for($grower)->for($variety, 'variety')->create();

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $grower))
        ->assertRedirect(route('admin.users.index'));

    expect(User::find($grower->id))->toBeNull();
    expect(Listing::find($listing->id))->toBeNull();
});

it('blocks deleting yourself and the last superuser', function () {
    $admin = User::factory()->superuser()->create();

    // Self-delete blocked.
    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $admin))
        ->assertSessionHasErrors('user');

    // Last-superuser delete blocked (second admin tries via a non-super target check).
    $second = User::factory()->superuser()->create();
    $this->actingAs($second)
        ->delete(route('admin.users.destroy', $admin))
        ->assertRedirect(); // two superusers exist — allowed

    expect(User::find($admin->id))->toBeNull();

    // Now $second is the only superuser; a fresh users-manager cannot delete them.
    $manager = User::factory()->create();
    $manager->givePermissionTo(Permissions::USERS_MANAGE);
    $this->actingAs($manager)
        ->delete(route('admin.users.destroy', $second))
        ->assertSessionHasErrors('user');

    expect(User::find($second->id))->not->toBeNull();
});

it('uploads and removes a profile avatar', function () {
    Storage::fake('public');
    $user = User::factory()->create();

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'avatar' => UploadedFile::fake()->image('me.jpg', 400, 400),
    ])->assertRedirect(route('profile.edit'));

    $path = $user->fresh()->avatar_path;
    expect($path)->not->toBeNull();
    Storage::disk('public')->assertExists($path);

    $this->actingAs($user)->patch(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'remove_avatar' => '1',
    ])->assertRedirect(route('profile.edit'));

    expect($user->fresh()->avatar_path)->toBeNull();
    Storage::disk('public')->assertMissing($path);
});
