<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\UpdatePreferencesRequest;
use App\Models\RoleApplication;
use App\Models\RoleDelegation;
use App\Models\User;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Roles;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): View
    {
        $user = $request->user();

        // applicableTo() collapses two rules into one list:
        //   - role isn't in the never-self-applicable set, AND
        //   - role's module (if any) is one the user has been enrolled in
        $applicableNames = Roles::applicableTo($user);
        $applicableRoles = Role::query()
            ->whereIn('name', $applicableNames)
            ->orderBy('name')
            ->get()
            ->reject(fn (Role $role) => $user->hasRole($role->name))
            ->values();

        $applicationsByRoleId = $user->roleApplications()
            ->with('role')
            ->latest('created_at')
            ->get()
            ->groupBy('role_id');

        // Roles the user holds that they're also allowed to delegate.
        $delegatableRolesHeld = Role::query()
            ->whereIn('name', Roles::delegatable())
            ->orderBy('name')
            ->get()
            ->filter(fn (Role $role) => $user->hasRole($role->name))
            ->values();

        $delegationsGranted = RoleDelegation::with(['recipient', 'role'])
            ->where('delegated_by', $user->id)
            ->active()
            ->latest('delegated_at')
            ->get();

        $delegationsReceived = RoleDelegation::with(['delegator', 'role'])
            ->where('user_id', $user->id)
            ->active()
            ->latest('delegated_at')
            ->get();

        return view('profile.edit', [
            'user' => $user,
            'varieties' => MangoVariety::query()->orderBy('name')->get(),
            'expertiseLevels' => User::EXPERTISE_LEVELS,
            'applicableRoles' => $applicableRoles,
            'roleApplicationsByRoleId' => $applicationsByRoleId,
            'roleApplicationStatuses' => [
                'pending' => RoleApplication::STATUS_PENDING,
                'approved' => RoleApplication::STATUS_APPROVED,
                'rejected' => RoleApplication::STATUS_REJECTED,
            ],
            'delegatableRolesHeld' => $delegatableRolesHeld,
            'delegationsGranted' => $delegationsGranted,
            'delegationsReceived' => $delegationsReceived,
        ]);
    }

    /**
     * Update the user's onboarding-style preferences (region, expertise,
     * favorite variety, notification opt-ins). Available to every onboarded
     * user — does not change roles, password, or onboarding-complete state.
     */
    public function updatePreferences(UpdatePreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        app(Telemetry::class)->record(
            Telemetry::PREFERENCES_UPDATED,
            subject: $user,
            context: [
                'region' => $user->region,
                'expertise' => $user->expertise,
                'favorite_variety_id' => $user->favorite_variety_id,
                'notify_seasonal' => $user->notify_seasonal,
                'subscribe_newsletter' => $user->subscribe_newsletter,
            ],
        );

        return Redirect::route('profile.edit')->with('status', 'preferences-updated');
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();
        $removeAvatar = (bool) ($data['remove_avatar'] ?? false);
        unset($data['avatar'], $data['remove_avatar']);

        if ($upload = $request->file('avatar')) {
            if ($user->avatar_path) {
                Storage::disk('public')->delete($user->avatar_path);
            }
            $data['avatar_path'] = $upload->store('avatars', 'public');
        } elseif ($removeAvatar && $user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
            $data['avatar_path'] = null;
        }

        $user->fill($data);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
