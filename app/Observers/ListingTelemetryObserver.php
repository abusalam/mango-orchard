<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Listing;
use App\Telemetry\Telemetry;

class ListingTelemetryObserver
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function created(Listing $listing): void
    {
        $this->telemetry->record(
            Telemetry::LISTING_CREATED,
            subject: $listing,
            context: [
                'farm' => $listing->farm_name,
                'variety_id' => $listing->mango_variety_id,
                'status' => $listing->status,
            ],
        );
    }

    public function updated(Listing $listing): void
    {
        $changed = array_keys($listing->getChanges());
        $changed = array_values(array_filter($changed, fn ($k) => $k !== 'updated_at'));

        if ($changed === []) {
            return;
        }

        $this->telemetry->record(
            Telemetry::LISTING_UPDATED,
            subject: $listing,
            context: [
                'farm' => $listing->farm_name,
                'changed' => $changed,
                'status' => $listing->status,
            ],
        );
    }

    public function deleted(Listing $listing): void
    {
        $this->telemetry->record(
            Telemetry::LISTING_DELETED,
            subject: $listing,
            context: ['farm' => $listing->farm_name],
        );
    }
}
