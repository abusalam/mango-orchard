<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Event;
use App\Permissions;
use Illuminate\View\View;

class EventController extends Controller
{
    public function index(): View
    {
        return view('events.index', [
            'upcoming' => Event::query()
                ->visible()
                ->where('start_at', '>=', now())
                ->orderBy('start_at')
                ->get(),
            'past' => Event::query()
                ->visible()
                ->where('start_at', '<', now())
                ->orderByDesc('start_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function show(Event $event): View
    {
        abort_unless(
            in_array($event->status, [
                Event::STATUS_PUBLISHED,
                Event::STATUS_CANCELLED,
                Event::STATUS_COMPLETED,
            ], true) || ($this->userCanManage()),
            404,
        );

        return view('events.show', ['event' => $event]);
    }

    private function userCanManage(): bool
    {
        return auth()->user()?->can(Permissions::EVENTS_MANAGE) ?? false;
    }
}
