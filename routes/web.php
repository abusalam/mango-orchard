<?php

use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\MangoVarietyController;
use App\Http\Controllers\OnboardingController;
use App\Http\Controllers\ProfileController;
use App\Models\MangoVariety;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'varieties' => MangoVariety::query()->orderBy('name')->get(),
    ]);
})->name('home');

Route::resource('varieties', MangoVarietyController::class)
    ->parameters(['varieties' => 'variety']);

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::prefix('admin')->name('admin.')->group(function () {
        Route::get('/', fn () => redirect()->route('admin.users.index'))->name('home');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::get('/users/{user}/edit', [UserController::class, 'edit'])->name('users.edit');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::resource('roles', RoleController::class)
            ->parameters(['roles' => 'role'])
            ->except(['show']);

        Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
        Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
    });

    Route::get('/onboarding', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::get('/onboarding/profile', [OnboardingController::class, 'showProfile'])->name('onboarding.profile');
    Route::post('/onboarding/profile', [OnboardingController::class, 'storeProfile'])->name('onboarding.profile.store');
    Route::get('/onboarding/preferences', [OnboardingController::class, 'showPreferences'])->name('onboarding.preferences');
    Route::post('/onboarding/preferences', [OnboardingController::class, 'storePreferences'])->name('onboarding.preferences.store');
});

require __DIR__.'/auth.php';
