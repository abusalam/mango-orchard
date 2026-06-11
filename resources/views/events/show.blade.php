<x-site-layout :title="$event->title.' — Training & Events'">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <nav class="text-sm text-stone-500 dark:text-stone-400 mb-6">
            <a href="{{ route('events.index') }}" class="hover:text-orange-700">Training & events</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ $event->title }}</span>
        </nav>

        @php
            $isOnline = stripos($event->location, 'online') === 0;
            $isPast = $event->isPast();
            $statusStyles = match ($event->status) {
                \App\Modules\MangoOrchard\Models\Event::STATUS_CANCELLED => 'bg-rose-100 text-rose-900 border border-rose-200',
                \App\Modules\MangoOrchard\Models\Event::STATUS_COMPLETED => 'bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300',
                \App\Modules\MangoOrchard\Models\Event::STATUS_DRAFT => 'bg-amber-100 text-amber-900 border border-amber-200 dark:border-stone-800',
                default => $isPast
                    ? 'bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300'
                    : 'bg-orange-100 text-orange-900 border border-orange-200',
            };
            $statusLabel = match ($event->status) {
                \App\Modules\MangoOrchard\Models\Event::STATUS_CANCELLED => 'Cancelled',
                \App\Modules\MangoOrchard\Models\Event::STATUS_COMPLETED => 'Completed',
                \App\Modules\MangoOrchard\Models\Event::STATUS_DRAFT => 'Draft',
                default => $isPast ? 'Past' : 'Upcoming',
            };
            $gradient = $isOnline
                ? 'from-yellow-200 via-amber-400 to-orange-500'
                : 'from-amber-300 via-orange-500 to-rose-500';
        @endphp

        <div class="rounded-3xl overflow-hidden border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-950 shadow-sm">
            <div class="relative h-44 sm:h-56 overflow-hidden bg-gradient-to-br {{ $gradient }}">
                <div aria-hidden="true" class="absolute -bottom-12 -right-8 w-72 h-80 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium {{ $statusStyles }}">{{ $statusLabel }}</span>
            </div>

            <div class="p-8 sm:p-10">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">{{ $event->title }}</h1>
                <p class="mt-2 text-stone-500 dark:text-stone-400">{{ $isOnline ? 'Online event' : $event->location }}</p>

                <dl class="mt-6 grid sm:grid-cols-2 gap-4 text-sm border-y border-stone-100 dark:border-stone-800 py-6">
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">When</dt>
                        <dd class="font-medium text-stone-800 dark:text-stone-200">
                            {{ $event->start_at->format('l, F j, Y') }}<br>
                            <span class="text-stone-600 dark:text-stone-300 font-normal">
                                {{ $event->start_at->format('g:i A') }}@if ($event->end_at) – {{ $event->end_at->format('g:i A') }}@endif
                            </span>
                        </dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">Where</dt>
                        <dd class="font-medium text-stone-800 dark:text-stone-200">
                            @if ($event->location_url)
                                <a href="{{ $event->location_url }}" target="_blank" rel="noopener noreferrer" class="text-orange-700 hover:underline">{{ $event->location }} ↗</a>
                            @else
                                {{ $event->location }}
                            @endif
                        </dd>
                    </div>
                    @if ($event->host)
                        <div>
                            <dt class="text-stone-500 dark:text-stone-400">Hosted by</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ $event->host }}</dd>
                        </div>
                    @endif
                    @if ($event->capacity)
                        <div>
                            <dt class="text-stone-500 dark:text-stone-400">Capacity</dt>
                            <dd class="font-medium text-stone-800 dark:text-stone-200">{{ number_format($event->capacity) }} attendees</dd>
                        </div>
                    @endif
                </dl>

                <div class="mt-6 prose prose-stone max-w-none">
                    <p class="text-stone-800 dark:text-stone-200 leading-relaxed whitespace-pre-line">{{ $event->description }}</p>
                </div>

                @if ($event->registration_url && ! $event->isPast() && $event->status === \App\Modules\MangoOrchard\Models\Event::STATUS_PUBLISHED)
                    <div class="mt-8 pt-6 border-t border-stone-100 dark:border-stone-800">
                        <a href="{{ $event->registration_url }}" target="_blank" rel="noopener noreferrer" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                            Register on the organiser's site ↗
                        </a>
                        <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">Registration is handled by {{ $event->host ?: 'the organiser' }}, not {{ config('app.name') }}.</p>
                    </div>
                @endif

                @can(\App\Permissions::EVENTS_MANAGE)
                    <div class="mt-8 pt-6 border-t border-stone-100 dark:border-stone-800 flex gap-3">
                        <a href="{{ route('admin.events.edit', $event) }}" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">Edit event</a>
                        <a href="{{ route('admin.events.index') }}" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 self-center">All events →</a>
                    </div>
                @endcan
            </div>
        </div>
    </section>
</x-site-layout>
