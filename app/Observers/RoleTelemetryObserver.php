<?php

declare(strict_types=1);

namespace App\Observers;

use App\Telemetry\Telemetry;
use Spatie\Permission\Models\Role;

class RoleTelemetryObserver
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function created(Role $role): void
    {
        $this->telemetry->record(
            Telemetry::ROLE_CREATED,
            subject: $role,
            context: ['name' => $role->name],
        );
    }

    public function updated(Role $role): void
    {
        $changed = array_keys($role->getChanges());
        $changed = array_values(array_filter($changed, fn ($k) => $k !== 'updated_at'));

        if ($changed === []) {
            return;
        }

        $this->telemetry->record(
            Telemetry::ROLE_UPDATED,
            subject: $role,
            context: ['changed' => $changed, 'name' => $role->name],
        );
    }

    public function deleted(Role $role): void
    {
        $this->telemetry->record(
            Telemetry::ROLE_DELETED,
            subject: $role,
            context: ['name' => $role->name],
        );
    }
}
