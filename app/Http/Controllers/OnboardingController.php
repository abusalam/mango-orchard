<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreOnboardingPreferencesRequest;
use App\Http\Requests\StoreOnboardingProfileRequest;
use App\Models\MangoVariety;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class OnboardingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function start(Request $request): RedirectResponse
    {
        $user = $request->user();

        if ($user->hasCompletedOnboarding()) {
            return redirect()->route('dashboard');
        }

        return redirect()->route('onboarding.'.$user->currentOnboardingStep());
    }

    public function showProfile(Request $request): View
    {
        return view('onboarding.profile', [
            'user' => $request->user(),
            'expertiseLevels' => \App\Models\User::EXPERTISE_LEVELS,
        ]);
    }

    public function storeProfile(StoreOnboardingProfileRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update($request->validated());

        app(Telemetry::class)->record(
            Telemetry::ONBOARDING_PROFILE_SAVED,
            subject: $user,
            context: ['region' => $user->region, 'expertise' => $user->expertise],
        );

        return redirect()->route('onboarding.preferences');
    }

    public function showPreferences(Request $request): View
    {
        return view('onboarding.preferences', [
            'user' => $request->user(),
            'varieties' => MangoVariety::query()->orderBy('name')->get(),
        ]);
    }

    public function storePreferences(StoreOnboardingPreferencesRequest $request): RedirectResponse
    {
        $user = $request->user();
        $user->update([
            ...$request->validated(),
            'onboarding_completed_at' => now(),
        ]);

        $telemetry = app(Telemetry::class);
        $telemetry->record(
            Telemetry::ONBOARDING_PREFERENCES_SAVED,
            subject: $user,
            context: [
                'favorite_variety_id' => $user->favorite_variety_id,
                'notify_seasonal' => $user->notify_seasonal,
                'subscribe_newsletter' => $user->subscribe_newsletter,
            ],
        );
        $telemetry->record(Telemetry::ONBOARDING_COMPLETED, subject: $user);

        return redirect()
            ->route('dashboard')
            ->with('status', "You're all set — welcome to the orchard.");
    }
}
