<?php

namespace App\Providers;

use App\Listeners\AssignSuperuserToFirstUser;
use App\Settings\Settings;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(Settings::class);
    }

    public function boot(): void
    {
        Event::listen(Registered::class, AssignSuperuserToFirstUser::class);
    }
}
