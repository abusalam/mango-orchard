<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Observers;

use App\Modules\MangoOrchard\Models\Event;
use App\Telemetry\Telemetry;

class EventTelemetryObserver
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function created(Event $event): void
    {
        $this->telemetry->record(
            Telemetry::EVENT_CREATED,
            subject: $event,
            context: [
                'title' => $event->title,
                'status' => $event->status,
                'start_at' => $event->start_at?->toIso8601String(),
            ],
        );
    }

    public function updated(Event $event): void
    {
        $changed = array_keys($event->getChanges());
        $changed = array_values(array_filter($changed, fn ($k) => $k !== 'updated_at'));

        if ($changed === []) {
            return;
        }

        $this->telemetry->record(
            Telemetry::EVENT_UPDATED,
            subject: $event,
            context: [
                'title' => $event->title,
                'changed' => $changed,
                'status' => $event->status,
            ],
        );
    }

    public function deleted(Event $event): void
    {
        $this->telemetry->record(
            Telemetry::EVENT_DELETED,
            subject: $event,
            context: ['title' => $event->title],
        );
    }
}
