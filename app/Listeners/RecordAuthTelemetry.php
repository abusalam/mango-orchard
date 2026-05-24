<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\User;
use App\Telemetry\Telemetry;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Auth\Events\Registered;

class RecordAuthTelemetry
{
    public function __construct(private readonly Telemetry $telemetry) {}

    public function onRegistered(Registered $event): void
    {
        if ($event->user instanceof User) {
            $this->telemetry->record(
                Telemetry::AUTH_REGISTERED,
                subject: $event->user,
                userId: $event->user->id,
            );
        }
    }

    public function onLogin(Login $event): void
    {
        if ($event->user instanceof User) {
            $this->telemetry->record(
                Telemetry::AUTH_LOGIN_SUCCEEDED,
                subject: $event->user,
                userId: $event->user->id,
            );
        }
    }

    public function onLogout(Logout $event): void
    {
        if ($event->user instanceof User) {
            $this->telemetry->record(
                Telemetry::AUTH_LOGOUT,
                subject: $event->user,
                userId: $event->user->id,
            );
        }
    }

    public function onFailed(Failed $event): void
    {
        $this->telemetry->record(
            Telemetry::AUTH_LOGIN_FAILED,
            context: [
                'email' => $event->credentials['email'] ?? null,
            ],
            userId: $event->user instanceof User ? $event->user->id : null,
        );
    }

    public function onPasswordReset(PasswordReset $event): void
    {
        if ($event->user instanceof User) {
            $this->telemetry->record(
                Telemetry::AUTH_PASSWORD_RESET_COMPLETED,
                subject: $event->user,
                userId: $event->user->id,
            );
        }
    }
}
