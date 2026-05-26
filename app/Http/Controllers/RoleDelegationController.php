<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreRoleDelegationRequest;
use App\Models\RoleDelegation;
use App\Models\User;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\Models\Role;

class RoleDelegationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function store(StoreRoleDelegationRequest $request): RedirectResponse
    {
        $delegator = $request->user();
        $role = Role::findOrFail((int) $request->validated('role_id'));
        $recipient = User::firstWhere('email', $request->validated('recipient_email'));

        $delegation = DB::transaction(function () use ($delegator, $role, $recipient) {
            $recipient->assignRole($role->name);

            return RoleDelegation::create([
                'user_id' => $recipient->id,
                'role_id' => $role->id,
                'delegated_by' => $delegator->id,
                'delegated_at' => now(),
            ]);
        });

        app(Telemetry::class)->record(
            Telemetry::ROLE_DELEGATED,
            subject: $delegation,
            context: [
                'role' => $role->name,
                'recipient_id' => $recipient->id,
                'recipient_email' => $recipient->email,
            ],
        );

        return redirect()
            ->route('profile.edit')
            ->with('status', "Delegated the {$role->name} role to {$recipient->name}.");
    }

    public function destroy(RoleDelegation $delegation): RedirectResponse
    {
        Gate::authorize('revoke', $delegation);

        $delegation->loadMissing(['role', 'recipient']);
        $actor = auth()->user();
        $role = $delegation->role;
        $recipient = $delegation->recipient;

        DB::transaction(function () use ($delegation, $actor, $recipient, $role) {
            $delegation->update([
                'revoked_at' => now(),
                'revoked_by' => $actor->id,
            ]);

            // Only strip the spatie role assignment if NO other active
            // delegation grants the same role to this recipient — multiple
            // delegators may have independently granted, and revoking one
            // shouldn't pull the rug on the others.
            $otherActive = RoleDelegation::active()
                ->where('user_id', $recipient->id)
                ->where('role_id', $role->id)
                ->where('id', '!=', $delegation->id)
                ->exists();

            if (! $otherActive && $recipient->hasRole($role->name)) {
                $recipient->removeRole($role->name);
            }
        });

        app(Telemetry::class)->record(
            Telemetry::ROLE_DELEGATION_REVOKED,
            subject: $delegation,
            context: [
                'role' => $role->name,
                'recipient_id' => $recipient->id,
                'revoked_by_role' => match (true) {
                    $actor->id === $delegation->delegated_by => 'delegator',
                    $actor->id === $delegation->user_id => 'recipient',
                    default => 'admin',
                },
            ],
        );

        return redirect()
            ->back()
            ->with('status', "Revoked the {$role->name} role from {$recipient->name}.");
    }
}
