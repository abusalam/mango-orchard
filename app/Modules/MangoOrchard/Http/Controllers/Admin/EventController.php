<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Modules\MangoOrchard\Models\Event;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class EventController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::EVENTS_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.events.index', [
            'events' => Event::query()->orderByDesc('start_at')->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.events.create', [
            'event' => new Event(['status' => Event::STATUS_DRAFT]),
        ]);
    }

    public function store(StoreEventRequest $request): RedirectResponse
    {
        $event = Event::create($request->validated());

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Created event '{$event->title}'.");
    }

    public function edit(Event $event): View
    {
        return view('admin.events.edit', ['event' => $event]);
    }

    public function update(UpdateEventRequest $request, Event $event): RedirectResponse
    {
        $event->update($request->validated());

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Updated event '{$event->title}'.");
    }

    public function destroy(Event $event): RedirectResponse
    {
        $title = $event->title;
        $event->delete();

        return redirect()
            ->route('admin.events.index')
            ->with('status', "Removed event '{$title}'.");
    }
}
