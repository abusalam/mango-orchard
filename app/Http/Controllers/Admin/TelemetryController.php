<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TelemetryEvent;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class TelemetryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::TELEMETRY_VIEW]),
        ];
    }

    public function index(Request $request): View
    {
        $filterEvent = $request->string('event')->toString();

        $events = TelemetryEvent::query()
            ->with('user')
            ->when($filterEvent !== '', fn ($q) => $q->where('event', $filterEvent))
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('admin.telemetry.index', [
            'events' => $events,
            'filterEvent' => $filterEvent,
            'eventOptions' => $this->knownEventOptions(),
        ]);
    }

    /**
     * Build a sorted, de-duplicated list of event names from telemetry rows
     * plus the constants defined in App\Telemetry\Telemetry, so the filter
     * dropdown always shows every known event (even before any have fired).
     *
     * @return list<string>
     */
    private function knownEventOptions(): array
    {
        $fromRows = TelemetryEvent::query()->distinct()->pluck('event')->all();

        $fromConstants = collect((new \ReflectionClass(Telemetry::class))->getConstants())
            ->filter(fn ($v) => is_string($v))
            ->values()
            ->all();

        $all = array_values(array_unique([...$fromRows, ...$fromConstants]));
        sort($all);

        return $all;
    }
}
