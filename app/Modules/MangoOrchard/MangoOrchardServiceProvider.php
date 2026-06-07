<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard;

use App\Modules\MangoOrchard\Console\Commands\DispatchSeasonalAlerts;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\ServiceProvider;

/**
 * Provider for the Mango Orchard module — currently registers the
 * seasonal-alert command + daily schedule. View loading, route binding
 * and policies for this module are still wired from the platform
 * defaults (the module uses unprefixed view paths and the policies are
 * auto-discovered via attributes); only background-job glue lives here.
 *
 * Schedule registration sits outside any `runningInConsole()` gate so
 * the admin System page (an HTTP request) can introspect events. Same
 * pattern as the Scheme Monitoring provider — see hard rule #17.
 */
class MangoOrchardServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([DispatchSeasonalAlerts::class]);
        }

        $this->app->booted(function (): void {
            $schedule = $this->app->make(Schedule::class);
            // 06:30 so the seasonal alert pass doesn't collide with the
            // 06:00 Pragati Darpan deadline reminder. Both are cheap, but
            // staggering keeps individual log lines readable.
            $schedule->command('mango:dispatch-seasonal-alerts')
                ->dailyAt('06:30')
                ->name('mango:dispatch-seasonal-alerts')
                ->withoutOverlapping();
        });
    }
}
