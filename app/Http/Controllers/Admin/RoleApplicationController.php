<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoleApplication;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RoleApplicationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::USERS_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.role-applications.index', [
            'pending' => RoleApplication::with(['user', 'role'])
                ->pending()
                ->latest('created_at')
                ->get(),
            'reviewed' => RoleApplication::with(['user', 'role', 'reviewer'])
                ->whereIn('status', [RoleApplication::STATUS_APPROVED, RoleApplication::STATUS_REJECTED])
                ->latest('reviewed_at')
                ->limit(25)
                ->get(),
        ]);
    }

    public function approve(Request $request, RoleApplication $application): RedirectResponse
    {
        Gate::authorize('review', RoleApplication::class);

        if (! $application->isPending()) {
            return back()->with('status', 'That application has already been reviewed.');
        }

        $application->loadMissing(['user', 'role']);

        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $note = (string) ($validated['decision_note'] ?? '');

        $application->user->assignRole($application->role->name);
        $application->update([
            'status' => RoleApplication::STATUS_APPROVED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_note' => $note !== '' ? $note : null,
        ]);

        app(Telemetry::class)->record(
            Telemetry::ROLE_APPLICATION_APPROVED,
            subject: $application,
            context: [
                'role' => $application->role->name,
                'applicant_id' => $application->user_id,
                'applicant_email' => $application->user->email,
            ],
        );

        return redirect()
            ->route('admin.role-applications.index')
            ->with('status', "Approved {$application->user->name} for the {$application->role->name} role.");
    }

    public function reject(Request $request, RoleApplication $application): RedirectResponse
    {
        Gate::authorize('review', RoleApplication::class);

        if (! $application->isPending()) {
            return back()->with('status', 'That application has already been reviewed.');
        }

        $application->loadMissing(['user', 'role']);

        $validated = $request->validate([
            'decision_note' => ['nullable', 'string', 'max:1000'],
        ]);
        $note = (string) ($validated['decision_note'] ?? '');

        $application->update([
            'status' => RoleApplication::STATUS_REJECTED,
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'decision_note' => $note !== '' ? $note : null,
        ]);

        app(Telemetry::class)->record(
            Telemetry::ROLE_APPLICATION_REJECTED,
            subject: $application,
            context: [
                'role' => $application->role->name,
                'applicant_id' => $application->user_id,
                'applicant_email' => $application->user->email,
            ],
        );

        return redirect()
            ->route('admin.role-applications.index')
            ->with('status', "Rejected {$application->user->name}'s application for the {$application->role->name} role.");
    }
}
