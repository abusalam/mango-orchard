<?php

declare(strict_types=1);

use App\Models\RoleApplication;
use App\Models\TelemetryEvent;
use App\Models\User;
use App\Roles;
use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

// ============== User-side: applying ==============

it('shows the request-role section on the profile page with each non-superuser role the user does not already hold', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/profile')
        ->assertOk()
        ->assertSee('Request a role')
        ->assertSee(Roles::GROWER)
        ->assertSee(Roles::EDITOR)
        ->assertSee(Roles::VIEWER)
        ->assertDontSee('superuser');
});

it('hides roles the user already holds from the apply-for list', function () {
    $user = User::factory()->grower()->create();

    $response = $this->actingAs($user)->get('/profile');

    // Each visible apply form carries a hidden role_id input — the grower role's
    // id should not appear there since the user already holds it.
    $growerId = Role::findByName(Roles::GROWER)->id;
    $response->assertOk()
        ->assertDontSee('value="'.$growerId.'"', escape: false);
});

it('allows an authenticated user to apply for a role', function () {
    $user = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($user)
        ->post('/role-applications', [
            'role_id' => $role->id,
            'message' => 'I run a small Alphonso orchard in Ratnagiri.',
        ])
        ->assertRedirect('/profile')
        ->assertSessionHas('status');

    expect(RoleApplication::query()->where('user_id', $user->id)->where('role_id', $role->id)->exists())
        ->toBeTrue();
});

it('rejects an application for the superuser role', function () {
    $user = User::factory()->create();
    $superuser = Role::findByName(Roles::SUPERUSER);

    $this->actingAs($user)
        ->from('/profile')
        ->post('/role-applications', [
            'role_id' => $superuser->id,
        ])
        ->assertSessionHasErrors('role_id');

    expect(RoleApplication::count())->toBe(0);
});

it('rejects an application for a role the user already holds', function () {
    $user = User::factory()->grower()->create();
    $grower = Role::findByName(Roles::GROWER);

    $this->actingAs($user)
        ->from('/profile')
        ->post('/role-applications', [
            'role_id' => $grower->id,
        ])
        ->assertSessionHasErrors('role_id');
});

it('rejects a second pending application for the same role', function () {
    $user = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);

    $this->actingAs($user)->post('/role-applications', ['role_id' => $role->id])->assertSessionHasNoErrors();
    $this->actingAs($user)
        ->from('/profile')
        ->post('/role-applications', ['role_id' => $role->id])
        ->assertSessionHasErrors('role_id');

    expect(RoleApplication::pending()->count())->toBe(1);
});

it('allows re-applying after a rejection', function () {
    $user = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);
    RoleApplication::factory()->create([
        'user_id' => $user->id,
        'role_id' => $role->id,
        'status' => RoleApplication::STATUS_REJECTED,
        'reviewed_at' => now()->subDay(),
    ]);

    $this->actingAs($user)
        ->post('/role-applications', ['role_id' => $role->id, 'message' => 'Trying again.'])
        ->assertSessionHasNoErrors()
        ->assertRedirect('/profile');

    expect(RoleApplication::pending()->where('user_id', $user->id)->count())->toBe(1);
});

it('lets a user cancel their own pending application', function () {
    $user = User::factory()->create();
    $app = RoleApplication::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->delete("/role-applications/{$app->id}")
        ->assertRedirect('/profile');

    expect(RoleApplication::find($app->id))->toBeNull();
});

it('forbids cancelling another user\'s pending application', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $app = RoleApplication::factory()->create(['user_id' => $owner->id]);

    $this->actingAs($other)
        ->delete("/role-applications/{$app->id}")
        ->assertForbidden();

    expect(RoleApplication::find($app->id))->not->toBeNull();
});

it('records telemetry when an application is submitted', function () {
    $user = User::factory()->create();
    $role = Role::findByName(Roles::EDITOR);

    $this->actingAs($user)->post('/role-applications', ['role_id' => $role->id]);

    expect(TelemetryEvent::where('event', Telemetry::ROLE_APPLICATION_SUBMITTED)->exists())->toBeTrue();
});

// ============== Admin-side: reviewing ==============

it('lists pending applications on the admin page', function () {
    $admin = User::factory()->superuser()->create();
    $applicant = User::factory()->create(['name' => 'Hopeful Grower']);
    RoleApplication::factory()->create([
        'user_id' => $applicant->id,
        'role_id' => Role::findByName(Roles::GROWER)->id,
        'message' => 'A unique-string-only-on-this-application',
    ]);

    $this->actingAs($admin)
        ->get('/admin/role-applications')
        ->assertOk()
        ->assertSee('Hopeful Grower')
        ->assertSee(Roles::GROWER)
        ->assertSee('A unique-string-only-on-this-application');
});

it('forbids non-admins from viewing the role applications page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/admin/role-applications')
        ->assertForbidden();
});

it('approves an application and assigns the requested role', function () {
    $admin = User::factory()->superuser()->create();
    $applicant = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);
    $app = RoleApplication::factory()->create([
        'user_id' => $applicant->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($admin)
        ->post("/admin/role-applications/{$app->id}/approve", ['decision_note' => 'Looks legit.'])
        ->assertRedirect('/admin/role-applications');

    expect($applicant->fresh()->hasRole($role->name))->toBeTrue();
    $app->refresh();
    expect($app->status)->toBe(RoleApplication::STATUS_APPROVED);
    expect($app->reviewed_by)->toBe($admin->id);
    expect($app->reviewed_at)->not->toBeNull();
    expect($app->decision_note)->toBe('Looks legit.');
});

it('rejects an application without assigning the role', function () {
    $admin = User::factory()->superuser()->create();
    $applicant = User::factory()->create();
    $role = Role::findByName(Roles::EDITOR);
    $app = RoleApplication::factory()->create([
        'user_id' => $applicant->id,
        'role_id' => $role->id,
    ]);

    $this->actingAs($admin)
        ->post("/admin/role-applications/{$app->id}/reject", ['decision_note' => 'Not yet.'])
        ->assertRedirect('/admin/role-applications');

    expect($applicant->fresh()->hasRole($role->name))->toBeFalse();
    expect($app->refresh()->status)->toBe(RoleApplication::STATUS_REJECTED);
    expect($app->decision_note)->toBe('Not yet.');
});

it('forbids non-admins from approving or rejecting', function () {
    $applicant = User::factory()->create();
    $other = User::factory()->create();
    $app = RoleApplication::factory()->create(['user_id' => $applicant->id]);

    $this->actingAs($other)->post("/admin/role-applications/{$app->id}/approve")->assertForbidden();
    $this->actingAs($other)->post("/admin/role-applications/{$app->id}/reject")->assertForbidden();
});

it('does not re-process an application that was already reviewed', function () {
    $admin = User::factory()->superuser()->create();
    $applicant = User::factory()->create();
    $role = Role::findByName(Roles::GROWER);
    $app = RoleApplication::factory()->create([
        'user_id' => $applicant->id,
        'role_id' => $role->id,
        'status' => RoleApplication::STATUS_REJECTED,
        'reviewed_at' => now()->subDay(),
    ]);

    $this->actingAs($admin)->post("/admin/role-applications/{$app->id}/approve");

    expect($applicant->fresh()->hasRole($role->name))->toBeFalse();
    expect($app->refresh()->status)->toBe(RoleApplication::STATUS_REJECTED);
});

it('records telemetry when an application is approved or rejected', function () {
    $admin = User::factory()->superuser()->create();
    $applicant = User::factory()->create();
    $app1 = RoleApplication::factory()->create(['user_id' => $applicant->id]);
    $app2 = RoleApplication::factory()->create([
        'user_id' => $applicant->id,
        'role_id' => Role::findByName(Roles::EDITOR)->id,
    ]);

    $this->actingAs($admin)->post("/admin/role-applications/{$app1->id}/approve");
    $this->actingAs($admin)->post("/admin/role-applications/{$app2->id}/reject");

    expect(TelemetryEvent::where('event', Telemetry::ROLE_APPLICATION_APPROVED)->exists())->toBeTrue();
    expect(TelemetryEvent::where('event', Telemetry::ROLE_APPLICATION_REJECTED)->exists())->toBeTrue();
});

it('shows the role applications link in the admin sidebar to users-manage users', function () {
    $admin = User::factory()->superuser()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Role applications')
        ->assertSee(route('admin.role-applications.index'));
});

it('hides the role applications link from admins who lack users-manage', function () {
    $role = Role::findOrCreate('settings-only', 'web');
    $role->syncPermissions([\App\Permissions::SETTINGS_MANAGE]);
    $user = User::factory()->create();
    $user->assignRole($role);

    $response = $this->actingAs($user)->get('/admin/settings');
    $response->assertOk();
    expect(str_contains($response->getContent(), route('admin.role-applications.index')))->toBeFalse();
});
