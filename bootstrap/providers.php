<?php

use App\Modules\MangoOrchard\MangoOrchardServiceProvider;
use App\Modules\SchemeMonitoring\SchemeMonitoringServiceProvider;
use App\Providers\AppServiceProvider;

return [
    AppServiceProvider::class,
    MangoOrchardServiceProvider::class,
    SchemeMonitoringServiceProvider::class,
];
