<x-site-layout :title="'Events — Admin'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">Training & education events</h1>
                <p class="mt-2 text-stone-600">Manage workshops, clinics, and webinars listed on the public events page.</p>
            </div>
            <a href="{{ route('admin.events.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                New event
            </a>
        </div>

        @if ($events->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                <p class="text-stone-600">No events yet.</p>
                <a href="{{ route('admin.events.create') }}" class="mt-4 inline-block text-orange-700 font-medium">Post the first one →</a>
            </div>
        @else
            <div class="rounded-2xl border border-stone-200 bg-white overflow-hidden">
                <table class="w-full text-sm table-fixed">
                    <thead class="bg-stone-50 text-stone-600 text-xs uppercase tracking-wider">
                        <tr>
                            <th class="px-4 py-3 text-left w-44">When</th>
                            <th class="px-4 py-3 text-left">Title</th>
                            <th class="px-4 py-3 text-left w-40">Location</th>
                            <th class="px-4 py-3 text-left w-28">Status</th>
                            <th class="px-4 py-3 w-20"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($events as $event)
                            <tr class="odd:bg-stone-50/60 hover:bg-amber-50/60 transition-colors">
                                <td class="px-4 py-3 whitespace-nowrap text-stone-700">{{ $event->start_at->format('M j, Y · g:i A') }}</td>
                                <td class="px-4 py-3">
                                    <a href="{{ route('events.show', $event) }}" class="font-medium text-stone-900 hover:text-orange-700">{{ $event->title }}</a>
                                    @if ($event->host)
                                        <div class="text-xs text-stone-500">{{ $event->host }}</div>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-stone-700">{{ $event->location }}</td>
                                <td class="px-4 py-3">
                                    @php
                                        $badge = match ($event->status) {
                                            \App\Modules\MangoOrchard\Models\Event::STATUS_PUBLISHED => 'bg-emerald-100 text-emerald-900',
                                            \App\Modules\MangoOrchard\Models\Event::STATUS_DRAFT => 'bg-amber-100 text-amber-900',
                                            \App\Modules\MangoOrchard\Models\Event::STATUS_CANCELLED => 'bg-rose-100 text-rose-900',
                                            \App\Modules\MangoOrchard\Models\Event::STATUS_COMPLETED => 'bg-stone-200 text-stone-700',
                                            default => 'bg-stone-100 text-stone-700',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $badge }}">{{ ucfirst($event->status) }}</span>
                                </td>
                                <td class="px-4 py-3 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.events.edit', $event) }}" class="px-2.5 py-1 rounded border border-stone-200 hover:border-stone-400 text-xs">Edit</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6">
                {{ $events->links() }}
            </div>
        @endif
    </section>
</x-site-layout>
