<x-admin-layout title="Activity" active="telemetry">
    <header class="mb-6 flex items-end justify-between gap-4">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Activity</h1>
            <p class="mt-1 text-stone-600">Recent events recorded by the app's telemetry hooks.</p>
        </div>
        <p class="text-sm text-stone-500" data-testid="telemetry-count">{{ $events->total() }} {{ Str::plural('event', $events->total()) }}</p>
    </header>

    <form method="GET" action="{{ route('admin.telemetry.index') }}" class="mb-4 flex items-center gap-3">
        <label for="event" class="text-sm text-stone-700">Filter by event:</label>
        <select name="event" id="event" onchange="this.form.submit()"
                class="rounded-lg border-stone-300 text-sm focus:border-orange-400 focus:ring-orange-400">
            <option value="">All events</option>
            @foreach ($eventOptions as $name)
                <option value="{{ $name }}" @selected($filterEvent === $name)>{{ $name }}</option>
            @endforeach
        </select>
        @if ($filterEvent !== '')
            <a href="{{ route('admin.telemetry.index') }}" class="text-xs text-stone-500 hover:text-stone-900">Clear</a>
        @endif
    </form>

    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-stone-50 text-stone-500 text-left">
                <tr>
                    <th class="px-5 py-3 font-medium">When</th>
                    <th class="px-5 py-3 font-medium">Event</th>
                    <th class="px-5 py-3 font-medium">Actor</th>
                    <th class="px-5 py-3 font-medium">Subject</th>
                    <th class="px-5 py-3 font-medium">Context</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-stone-100">
                @forelse ($events as $event)
                    <tr data-testid="telemetry-row">
                        <td class="px-5 py-3 text-stone-600 whitespace-nowrap" title="{{ $event->occurred_at }}">
                            {{ $event->occurred_at->diffForHumans() }}
                        </td>
                        <td class="px-5 py-3 font-mono text-xs text-stone-800">{{ $event->event }}</td>
                        <td class="px-5 py-3 text-stone-700">
                            @if ($event->user)
                                {{ $event->user->name }}
                                <span class="text-xs text-stone-400">{{ $event->user->email }}</span>
                            @else
                                <span class="text-stone-400 italic">guest</span>
                            @endif
                            <x-impersonated-tag :event="$event" />
                        </td>
                        <td class="px-5 py-3 text-stone-700 font-mono text-xs">
                            @if ($event->subject_type)
                                {{ class_basename($event->subject_type) }}#{{ $event->subject_id }}
                            @else
                                <span class="text-stone-400 italic">—</span>
                            @endif
                        </td>
                        <td class="px-5 py-3 text-xs text-stone-600">
                            @if ($event->context)
                                <code class="block max-w-md truncate" title="{{ json_encode($event->context) }}">{{ json_encode($event->context) }}</code>
                            @else
                                <span class="text-stone-400 italic">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="px-5 py-10 text-center text-stone-500" data-testid="telemetry-empty">
                            No events recorded yet.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $events->links() }}
    </div>
</x-admin-layout>
