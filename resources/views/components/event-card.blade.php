@props(['event', 'past' => false])

@php
    $isOnline = stripos($event->location, 'online') === 0;
    $statusStyles = match ($event->status) {
        \App\Modules\MangoOrchard\Models\Event::STATUS_CANCELLED => 'bg-rose-100 text-rose-900 border border-rose-200',
        \App\Modules\MangoOrchard\Models\Event::STATUS_COMPLETED => 'bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300',
        \App\Modules\MangoOrchard\Models\Event::STATUS_DRAFT => 'bg-amber-100 text-amber-900 border border-amber-200 dark:border-stone-800',
        default => $past
            ? 'bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300'
            : 'bg-orange-100 text-orange-900 border border-orange-200',
    };
    $statusLabel = match ($event->status) {
        \App\Modules\MangoOrchard\Models\Event::STATUS_CANCELLED => 'Cancelled',
        \App\Modules\MangoOrchard\Models\Event::STATUS_COMPLETED => 'Completed',
        \App\Modules\MangoOrchard\Models\Event::STATUS_DRAFT => 'Draft',
        default => $past ? 'Past' : 'Upcoming',
    };
    // Mirror the warm sunset gradients used on variety / listing cards — full
    // mango for in-person events, lighter golden for online sessions so the
    // two stay visually distinct without leaving the brand palette.
    $gradient = $isOnline
        ? 'from-yellow-200 via-amber-400 to-orange-500'
        : 'from-amber-300 via-orange-500 to-rose-500';
@endphp

<article class="group relative overflow-hidden rounded-2xl bg-white dark:bg-stone-950 border border-stone-200/80 hover:border-stone-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
    <a href="{{ route('events.show', $event) }}" class="block">
        <div class="relative h-40 overflow-hidden bg-gradient-to-br {{ $gradient }}">
            <div aria-hidden="true" class="absolute -bottom-10 -right-6 w-44 h-52 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
            <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $statusStyles }}">{{ $statusLabel }}</span>
        </div>
        <div class="p-5">
            <h2 class="text-lg font-semibold tracking-tight">{{ $event->title }}</h2>
            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $isOnline ? 'Online' : $event->location }}</p>
            <p class="mt-3 text-sm font-medium text-stone-800 dark:text-stone-200">{{ $event->start_at->format('D, M j') }} <span class="text-stone-500 dark:text-stone-400 font-normal">· {{ $event->start_at->format('g:i A') }}</span></p>
            @if ($event->host)
                <p class="text-xs text-stone-500 dark:text-stone-400">Hosted by {{ $event->host }}</p>
            @endif
        </div>
    </a>
</article>
