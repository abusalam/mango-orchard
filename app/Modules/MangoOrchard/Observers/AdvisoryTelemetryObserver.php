<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Observers;

use App\Modules\MangoOrchard\Models\Advisory;
use App\Telemetry\Telemetry;

class AdvisoryTelemetryObserver
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function created(Advisory $advisory): void
    {
        $this->telemetry->record(
            Telemetry::ADVISORY_CREATED,
            subject: $advisory,
            context: [
                'title' => $advisory->title,
                'category' => $advisory->category,
                'severity' => $advisory->severity,
                'published' => $advisory->published,
            ],
        );
    }

    public function updated(Advisory $advisory): void
    {
        $changed = array_values(array_filter(
            array_keys($advisory->getChanges()),
            fn ($k) => $k !== 'updated_at',
        ));

        if ($changed === []) {
            return;
        }

        $this->telemetry->record(
            Telemetry::ADVISORY_UPDATED,
            subject: $advisory,
            context: [
                'title' => $advisory->title,
                'changed' => $changed,
            ],
        );
    }

    public function deleted(Advisory $advisory): void
    {
        $this->telemetry->record(
            Telemetry::ADVISORY_DELETED,
            subject: $advisory,
            context: [
                'title' => $advisory->title,
                'category' => $advisory->category,
            ],
        );
    }
}
