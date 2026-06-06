<x-site-layout :title="'Training & Events — Aamar Malda'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-medium tracking-wide uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                Training & education
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl font-semibold tracking-tight">Workshops, clinics & field days for mango growers</h1>
            <p class="mt-3 text-stone-600 max-w-2xl">Practical sessions on pruning, pest management, post-harvest handling, certification and more — run by extension services, universities and grower associations.</p>
            @can(\App\Permissions::EVENTS_MANAGE)
                <div class="mt-5">
                    <a href="{{ route('admin.events.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                        Post a new event →
                    </a>
                </div>
            @endcan
        </header>

        <h2 class="text-xs font-semibold tracking-wide uppercase text-stone-500 mb-4">Upcoming</h2>
        @if ($upcoming->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center mb-12">
                <p class="text-stone-600">No upcoming events scheduled right now. Check back soon.</p>
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-14">
                @foreach ($upcoming as $event)
                    <x-event-card :event="$event" />
                @endforeach
            </div>
        @endif

        @if ($past->isNotEmpty())
            <h2 class="text-xs font-semibold tracking-wide uppercase text-stone-500 mb-4">Past events</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($past as $event)
                    <x-event-card :event="$event" :past="true" />
                @endforeach
            </div>
        @endif
    </section>
</x-site-layout>
