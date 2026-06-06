<?php

declare(strict_types=1);

use App\Models\User;

it('redirects a guest from the monitoring dashboard to login', function () {
    $this->get(route('monitoring.dashboard'))->assertRedirect(route('login'));
});

it('returns 403 for an authed user without monitoring.view', function () {
    $user = User::factory()->create();
    $this->actingAs($user)->get(route('monitoring.dashboard'))->assertForbidden();
});

it('lets a monitor reach the dashboard', function () {
    $monitor = User::factory()->monitor()->create();
    $this->actingAs($monitor)->get(route('monitoring.dashboard'))->assertOk();
});

it('lets a superuser reach the dashboard', function () {
    $admin = User::factory()->superuser()->create();
    $this->actingAs($admin)->get(route('monitoring.dashboard'))->assertOk();
});

it('returns 403 from admin designation index without monitoring.manage', function () {
    $monitor = User::factory()->monitor()->create();
    $this->actingAs($monitor)
        ->get(route('admin.monitoring.designations.index'))
        ->assertForbidden();
});

it('lets a monitor-admin reach admin designation index', function () {
    $monitorAdmin = User::factory()->monitorAdmin()->create();
    $this->actingAs($monitorAdmin)
        ->get(route('admin.monitoring.designations.index'))
        ->assertOk();
});
