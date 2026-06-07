<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring;

use App\Modules\SchemeMonitoring\Console\Commands\DispatchDeadlineReminders;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Policies\SchemePolicy;
use App\Modules\SchemeMonitoring\Policies\TaskPolicy;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class SchemeMonitoringServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Hierarchy::class);
    }

    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../../../database/migrations/scheme-monitoring');

        $this->loadViewsFrom(
            __DIR__.'/../../../resources/views/modules/scheme-monitoring',
            'scheme-monitoring',
        );

        Route::middleware('web')->group(__DIR__.'/../../../routes/modules/scheme-monitoring.php');

        Gate::policy(Scheme::class, SchemePolicy::class);
        Gate::policy(Task::class, TaskPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([DispatchDeadlineReminders::class]);
        }

        // Register the schedule outside the CLI-only block so the admin
        // System page (which resolves Schedule in an HTTP request) can
        // introspect it. The schedule itself only FIRES from CLI via
        // `php artisan schedule:run`, so adding events here has no
        // runtime cost in HTTP — just makes the in-memory event list
        // populated everywhere.
        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            $schedule->command('monitoring:dispatch-deadline-reminders')
                ->dailyAt('06:00')
                ->name('monitoring:dispatch-deadline-reminders')
                ->withoutOverlapping();
        });
    }
}
