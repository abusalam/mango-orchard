<?php

use App\Http\Controllers\Admin\ImpersonationController as AdminImpersonationController;
use App\Http\Controllers\Admin\RoleApplicationController as AdminRoleApplicationController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\TelemetryController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ListingController;
use App\Http\Controllers\MangoVarietyController;
use App\Http\Controllers\My\ListingController as MyListingController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleApplicationController;
use App\Models\MangoVariety;
use App\Permissions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'varieties' => MangoVariety::query()->orderBy('name')->get(),
    ]);
})->name('home');

Route::resource('varieties', MangoVarietyController::class)
    ->parameters(['varieties' => 'variety']);

// Public marketplace (browse + detail of published listings).
Route::get('/listings', [ListingController::class, 'index'])->name('listings.index');
Route::get('/listings/{listing}', [ListingController::class, 'show'])->name('listings.show');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/preferences', [ProfileController::class, 'updatePreferences'])->name('profile.preferences.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Self-service role applications.
    Route::post('/role-applications', [RoleApplicationController::class, 'store'])->name('role-applications.store');
    Route::delete('/role-applications/{application}', [RoleApplicationController::class, 'destroy'])->name('role-applications.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        // Send each admin to the first section they actually have permission for,
        // so the "Admin" nav link never lands on a 403.
        Route::get('/', function () {
            $destinations = [
                Permissions::USERS_MANAGE => 'admin.users.index',
                Permissions::ROLES_MANAGE => 'admin.roles.index',
                Permissions::SETTINGS_MANAGE => 'admin.settings.edit',
                Permissions::TELEMETRY_VIEW => 'admin.telemetry.index',
                Permissions::USERS_IMPERSONATE => 'admin.impersonate.index',
            ];

            foreach ($destinations as $permission => $route) {
                if (Auth::user()?->can($permission)) {
                    return redirect()->route($route);
                }
            }

            abort(403);
        })->name('home');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::resource('roles', RoleController::class)
            ->parameters(['roles' => 'role'])
            ->except(['show']);

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');

        Route::get('/telemetry', [TelemetryController::class, 'index'])->name('telemetry.index');

        Route::get('/role-applications', [AdminRoleApplicationController::class, 'index'])->name('role-applications.index');
        Route::post('/role-applications/{application}/approve', [AdminRoleApplicationController::class, 'approve'])->name('role-applications.approve');
        Route::post('/role-applications/{application}/reject', [AdminRoleApplicationController::class, 'reject'])->name('role-applications.reject');

        Route::get('/impersonate', [AdminImpersonationController::class, 'index'])->name('impersonate.index');
        Route::post('/impersonate/users/{user}', [AdminImpersonationController::class, 'impersonateUser'])->name('impersonate.user');
        Route::post('/impersonate/roles/{role}', [AdminImpersonationController::class, 'impersonateRole'])->name('impersonate.role');
    });

    // Stop-impersonation lives outside the admin permission group because the
    // target (impersonated) user might not hold any admin permission at all.
    Route::post('/impersonate/stop', [ImpersonationController::class, 'stop'])->name('impersonate.stop');

    // A grower's own listings (CRUD restricted to the listing owner).
    Route::prefix('my')->name('my.')->group(function () {
        Route::get('/listings', [MyListingController::class, 'index'])->name('listings.index');
        Route::get('/listings/create', [MyListingController::class, 'create'])->name('listings.create');
        Route::post('/listings', [MyListingController::class, 'store'])->name('listings.store');
        Route::get('/listings/{listing}/edit', [MyListingController::class, 'edit'])->name('listings.edit');
        Route::put('/listings/{listing}', [MyListingController::class, 'update'])->name('listings.update');
        Route::delete('/listings/{listing}', [MyListingController::class, 'destroy'])->name('listings.destroy');
    });

    Route::get('/onboarding', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::get('/onboarding/profile', [OnboardingController::class, 'showProfile'])->name('onboarding.profile');
    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])->name('onboarding.profile.store');
    Route::get('/onboarding/preferences', [OnboardingController::class, 'showPreferences'])->name('onboarding.preferences');
    Route::post('/onboarding/preferences', [OnboardingController::class, 'storePreferences'])->name('onboarding.preferences.store');
});

require __DIR__.'/auth.php';
