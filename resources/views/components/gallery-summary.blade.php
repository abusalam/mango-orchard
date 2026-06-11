@php
    $albums = \App\Models\GalleryAlbum::query()
        ->where('published', true)
        ->orderBy('display_order')
        ->withCount('photos')
        ->with('coverPhoto')
        ->limit(3)
        ->get();
    $totalAlbums = \App\Models\GalleryAlbum::query()->where('published', true)->count();
@endphp

@if ($albums->isNotEmpty())
    <section class="bg-amber-50 dark:bg-stone-900 border-t border-amber-100 dark:border-stone-800">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center mb-10">
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-amber-900 dark:text-amber-200">Mango Gallery</h2>
                <div aria-hidden="true" class="mt-3 mx-auto w-16 h-0.5 bg-gradient-to-r from-amber-500 to-rose-500"></div>
                <p class="mt-4 max-w-2xl mx-auto text-stone-700 dark:text-stone-300 text-sm">Photographs from {{ config('app.district') }}'s orchards, pack-houses, and mango fairs.</p>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 max-w-5xl mx-auto">
                @foreach ($albums as $album)
                    <a href="{{ route('gallery.show', $album) }}"
                       class="group block bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden hover:border-amber-400 dark:hover:border-amber-700 hover:shadow-lg transition-all"
                       data-testid="gallery-summary-album-{{ $album->slug }}">
                        <div class="relative aspect-[4/3] bg-gradient-to-br from-amber-100 to-emerald-100 dark:from-stone-800 dark:to-stone-900">
                            @if ($album->coverPhoto)
                                <img src="{{ $album->coverPhoto->thumbnailUrl() }}" alt="{{ $album->title }}"
                                     class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                     loading="lazy" decoding="async">
                            @else
                                <div class="absolute inset-0 flex items-center justify-center text-stone-400 text-4xl">📷</div>
                            @endif
                            <span class="absolute bottom-2 right-2 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-900/80 text-white backdrop-blur">{{ $album->photos_count }}</span>
                        </div>
                        <div class="p-3">
                            <p class="font-medium text-stone-900 dark:text-stone-100 truncate">{{ $album->title }}</p>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('gallery.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-amber-600 text-white font-medium hover:bg-amber-700 transition-colors" data-testid="gallery-summary-cta">
                    View Mango Gallery
                    @if ($totalAlbums > $albums->count())
                        <span class="text-amber-100 text-xs">({{ $totalAlbums }} albums)</span>
                    @endif
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
        </div>
    </section>
@endif
