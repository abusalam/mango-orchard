<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleApplicationRequest;
use App\Models\RoleApplication;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;

class RoleApplicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function store(StoreRoleApplicationRequest $request): RedirectResponse
    {
        $application = $request->user()->roleApplications()->create([
            'role_id' => (int) $request->validated('role_id'),
            'message' => $request->validated('message'),
            'status' => RoleApplication::STATUS_PENDING,
        ]);

        $application->loadMissing('role');

        app(Telemetry::class)->record(
            Telemetry::ROLE_APPLICATION_SUBMITTED,
            subject: $application,
            context: [
                'role' => $application->role?->name,
                'message_length' => $application->message ? strlen($application->message) : 0,
            ],
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', "Applied for the {$application->role->name} role. An admin will review your request.");
    }

    public function destroy(RoleApplication $application): RedirectResponse
    {
        Gate::authorize('cancel', $application);

        $application->loadMissing('role');
        $roleName = $application->role?->name;
        $application->delete();

        app(Telemetry::class)->record(
            Telemetry::ROLE_APPLICATION_CANCELLED,
            subject: $application,
            context: ['role' => $roleName],
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', "Cancelled your application for the {$roleName} role.");
    }
}
