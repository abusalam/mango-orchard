<?php

namespace App\Providers;

use App\Listeners\AssignSuperuserToFirstUser;
use App\Listeners\RecordAuthTelemetry;
use App\Observers\RoleTelemetryObserver;
use App\Services\Impersonation;
use App\Settings\Settings;
use App\Telemetry\Telemetry;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;
use Illuminate\Queue\Events\Looping as QueueLooping;
use Illuminate\Queue\Events\WorkerStopping as QueueWorkerStopping;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
        $this->app->singleton(Telemetry::class);
        $this->app->singleton(Impersonation::class);
    }

    public function boot(): void
    {
        Event::listen(Registered::class, AssignSuperuserToFirstUser::class);

        // Queue-worker heartbeat. `Looping` fires on every poll cycle —
        // multiple times per second when idle, once per job otherwise.
        // The cache key TTLs out after 60s, so the admin System page can
        // tell whether the worker is alive (heartbeat seen recently) or
        // stopped (no heartbeat / stale). `WorkerStopping` proactively
        // clears the key so a graceful shutdown shows "stopped" instantly.
        Event::listen(QueueLooping::class, fn () => Cache::put('queue:worker:heartbeat', now()->timestamp, 60));
        Event::listen(QueueWorkerStopping::class, fn () => Cache::forget('queue:worker:heartbeat'));

        Event::listen(Registered::class, [RecordAuthTelemetry::class, 'onRegistered']);
        Event::listen(Login::class, [RecordAuthTelemetry::class, 'onLogin']);
        Event::listen(Logout::class, [RecordAuthTelemetry::class, 'onLogout']);
        Event::listen(Failed::class, [RecordAuthTelemetry::class, 'onFailed']);
        Event::listen(PasswordReset::class, [RecordAuthTelemetry::class, 'onPasswordReset']);

        Role::observe(RoleTelemetryObserver::class);

        View::composer('*', function ($view): void {
            $view->with('formAutofillEnabled', app(Settings::class)->formAutofill());
            $view->with('readonlyModeEnabled', app(Settings::class)->readonlyMode());
            $view->with('devBannerEnabled', app(Settings::class)->devBannerEnabled());
            $view->with('appVersionTag', \App\Support\Version::tag());

            $impersonation = app(Impersonation::class);
            if ($impersonation->isActive() && ($target = auth()->user()) !== null && ($actor = $impersonation->originalUser()) !== null) {
                $reason = (string) session(Impersonation::SESSION_REASON_KEY, 'user');
                $reasonLabel = str_starts_with($reason, 'role:')
                    ? 'role: '.substr($reason, 5)
                    : null;

                $view->with('impersonating', [
                    'actor_name' => $actor->name,
                    'actor_email' => $actor->email,
                    'target_name' => $target->name,
                    'target_email' => $target->email,
                    'reason' => $reason,
                    'reason_label' => $reasonLabel,
                ]);
            } else {
                $view->with('impersonating', null);
            }
        });
    }
}
