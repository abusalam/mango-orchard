<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Observers;

use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Telemetry\Telemetry;

class MangoVarietyTelemetryObserver
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function created(MangoVariety $variety): void
    {
        $this->telemetry->record(
            Telemetry::VARIETY_CREATED,
            subject: $variety,
            context: ['name' => $variety->name, 'slug' => $variety->slug],
        );
    }

    public function updated(MangoVariety $variety): void
    {
        $changed = array_keys($variety->getChanges());
        $changed = array_values(array_filter($changed, fn ($k) => $k !== 'updated_at'));

        if ($changed === []) {
            return;
        }

        $this->telemetry->record(
            Telemetry::VARIETY_UPDATED,
            subject: $variety,
            context: ['changed' => $changed, 'name' => $variety->name],
        );
    }

    public function deleted(MangoVariety $variety): void
    {
        $this->telemetry->record(
            Telemetry::VARIETY_DELETED,
            subject: $variety,
            context: ['name' => $variety->name],
        );
    }
}
