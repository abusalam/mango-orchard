<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Roles;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;
use Intervention\Image\ImageManager;

/**
 * First-run site setup. Reachable ONLY while no user exists (enforced
 * both by RequireSiteSetup redirecting everything here and by the guard
 * at the top of each action so the wizard can't be replayed later).
 */
class SetupController extends Controller
{
    public function show(Settings $settings): View
    {
        $this->guardFreshInstall($settings);

        return view('setup');
    }

    public function store(Request $request, Settings $settings): RedirectResponse
    {
        $this->guardFreshInstall($settings);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:2048'],
        ]);

        $admin = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'onboarding_completed_at' => now(),
        ]);
        $admin->forceFill(['email_verified_at' => now()])->save();
        $admin->assignRole(Roles::SUPERUSER);

        if ($upload = $request->file('logo')) {
            // Square-ish WebP at 512px — same intervention/image GD pipeline
            // the gallery uses, but written under branding/.
            $image = ImageManager::gd()->read($upload->getRealPath())->scaleDown(width: 512);
            $path = 'branding/logo-'.Str::random(12).'.webp';
            Storage::disk('public')->put($path, (string) $image->toWebp(quality: 90));
            $settings->set(Settings::SITE_LOGO_PATH, $path);
        }

        $settings->set(Settings::SITE_SETUP_COMPLETED, true);

        app(Telemetry::class)->record('site.setup_completed', context: [
            'admin_email' => $admin->email,
            'logo_uploaded' => $request->hasFile('logo'),
        ]);

        Auth::login($admin);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard')
            ->with('status', 'Site setup complete — welcome! You can change the logo any time under Admin → Settings.');
    }

    private function guardFreshInstall(Settings $settings): void
    {
        abort_if($settings->setupCompleted() || User::query()->count() > 0, 403, 'Site setup has already been completed.');
    }
}
