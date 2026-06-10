<x-site-layout :title="$album->title.' — Mango Gallery'">
    <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <a href="{{ route('gallery.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-4">
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
            </svg>
            All albums
        </a>

        <header class="mb-8 flex items-end justify-between gap-4 flex-wrap">
            <div>
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $album->title }}</h1>
                @if ($album->description)
                    <p class="mt-2 max-w-2xl text-stone-600 dark:text-stone-300">{{ $album->description }}</p>
                @endif
                <p class="mt-2 text-xs text-stone-500 dark:text-stone-400">{{ $album->photos->count() }} {{ Str::plural('photo', $album->photos->count()) }}</p>
            </div>
            @can(\App\Permissions::GALLERY_MANAGE)
                <a href="{{ route('admin.gallery.albums.edit', $album) }}"
                   class="inline-flex items-center gap-1 px-3 py-1.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 text-sm transition-colors"
                   data-testid="manage-this-album">
                    Manage album →
                </a>
            @endcan
        </header>

        @if ($album->photos->isEmpty())
            <p class="text-center text-stone-500 dark:text-stone-400 italic py-12">No photos in this album yet.</p>
        @else
            @php
                // Pre-compute the photos payload as a plain variable so the
                // inline @json() call below has no nested brackets to confuse
                // Blade's directive parser.
                $photosJson = $album->photos->map(fn ($p) => [
                    'url' => $p->url(),
                    'thumb' => $p->thumbnailUrl(),
                    'caption' => $p->caption,
                    'alt' => $p->alt_text ?? $album->title,
                    'width' => $p->width,
                    'height' => $p->height,
                ])->all();
            @endphp

            {{-- Lightbox: Alpine state tracks the active photo index. Click a
                 thumb to open; ← / → / Esc to navigate; click backdrop to close. --}}
            <div x-data='{
                    open: false,
                    index: 0,
                    photos: @json($photosJson),
                    show(i) { this.index = i; this.open = true; document.body.style.overflow = "hidden"; },
                    close() { this.open = false; document.body.style.overflow = ""; },
                    next() { this.index = (this.index + 1) % this.photos.length; },
                    prev() { this.index = (this.index - 1 + this.photos.length) % this.photos.length; }
                 }'
                 @keydown.window.escape="if (open) close()"
                 @keydown.window.right="if (open) next()"
                 @keydown.window.left="if (open) prev()"
                 data-testid="gallery-photo-grid">

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3 sm:gap-4">
                    @foreach ($album->photos as $i => $photo)
                        <button type="button" @click="show({{ $i }})"
                                class="group relative aspect-square rounded-xl overflow-hidden bg-stone-100 dark:bg-stone-900 border border-stone-200 dark:border-stone-800 hover:border-amber-400 dark:hover:border-amber-700 hover:shadow-md transition-all focus:outline-none focus:ring-2 focus:ring-amber-400"
                                aria-label="Open photo {{ $i + 1 }}"
                                data-testid="gallery-photo-{{ $i }}">
                            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->alt_text ?? $album->title }}"
                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                 loading="lazy" decoding="async"
                                 @if ($photo->width && $photo->height) width="{{ $photo->width }}" height="{{ $photo->height }}" @endif>
                            @if ($photo->caption)
                                <span class="absolute bottom-0 inset-x-0 bg-gradient-to-t from-stone-900/80 to-transparent text-white text-xs px-2 py-1.5 text-left line-clamp-2 opacity-0 group-hover:opacity-100 transition-opacity">{{ $photo->caption }}</span>
                            @endif
                        </button>
                    @endforeach
                </div>

                {{-- Lightbox modal --}}
                <div x-show="open" x-cloak
                     x-transition.opacity
                     class="fixed inset-0 z-50 bg-stone-950/95 flex items-center justify-center p-4 sm:p-8"
                     @click.self="close()"
                     role="dialog" aria-modal="true" aria-label="Photo viewer">

                    {{-- Close --}}
                    <button type="button" @click="close()"
                            class="absolute top-3 right-3 sm:top-6 sm:right-6 inline-flex items-center justify-center w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur transition-colors"
                            aria-label="Close">
                        <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/></svg>
                    </button>

                    {{-- Prev / next (hidden when only 1 photo) --}}
                    <template x-if="photos.length > 1">
                        <div>
                            <button type="button" @click.stop="prev()"
                                    class="absolute left-2 sm:left-6 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur transition-colors"
                                    aria-label="Previous">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 4l-6 6 6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                            <button type="button" @click.stop="next()"
                                    class="absolute right-2 sm:right-6 top-1/2 -translate-y-1/2 inline-flex items-center justify-center w-11 h-11 rounded-full bg-white/10 hover:bg-white/20 text-white backdrop-blur transition-colors"
                                    aria-label="Next">
                                <svg class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M8 4l6 6-6 6" stroke-linecap="round" stroke-linejoin="round"/></svg>
                            </button>
                        </div>
                    </template>

                    {{-- The active image --}}
                    <figure class="relative max-w-full max-h-full flex flex-col items-center" @click.stop>
                        <img :src="photos[index].url" :alt="photos[index].alt"
                             class="max-w-full max-h-[80vh] object-contain rounded-lg shadow-2xl">
                        <figcaption x-show="photos[index].caption" class="mt-3 max-w-2xl text-center text-sm text-stone-200" x-text="photos[index].caption"></figcaption>
                        <p class="mt-2 text-xs text-stone-400" x-text="(index + 1) + ' / ' + photos.length"></p>
                    </figure>
                </div>
            </div>
        @endif
    </main>
</x-site-layout>
