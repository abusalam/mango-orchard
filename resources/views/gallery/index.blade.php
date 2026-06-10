<x-site-layout title="Mango Gallery — Aamar Malda">
    <main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
        <header class="mb-10 text-center">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-emerald-900 dark:text-emerald-200">Mango Gallery</h1>
            <div aria-hidden="true" class="mt-3 mx-auto w-16 h-0.5 bg-gradient-to-r from-emerald-500 to-amber-500"></div>
            <p class="mt-4 max-w-2xl mx-auto text-stone-600 dark:text-stone-300">Photographs from the orchards of Malda — variety closeups, harvest scenes, pack-houses, fairs and field events.</p>
        </header>

        @if ($albums->isEmpty())
            <p class="text-center text-stone-500 dark:text-stone-400 italic py-12">No albums published yet.</p>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6" data-testid="gallery-album-grid">
                @foreach ($albums as $album)
                    <article class="group bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden hover:border-amber-400 dark:hover:border-amber-700 hover:shadow-lg transition-all"
                             data-testid="gallery-album-card-{{ $album->slug }}">
                        <a href="{{ route('gallery.show', $album) }}" class="block">
                            <div class="relative aspect-[4/3] bg-gradient-to-br from-amber-100 to-emerald-100 dark:from-stone-800 dark:to-stone-900">
                                @if ($album->coverPhoto)
                                    <img src="{{ $album->coverPhoto->thumbnailUrl() }}" alt="{{ $album->coverPhoto->alt_text ?? $album->title }}"
                                         class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                         loading="lazy" decoding="async">
                                @else
                                    <div class="absolute inset-0 flex items-center justify-center text-stone-400 dark:text-stone-600 text-4xl">📷</div>
                                @endif
                                <span class="absolute bottom-3 right-3 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-900/80 text-white backdrop-blur">{{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }}</span>
                            </div>
                            <div class="p-4">
                                <h2 class="font-semibold text-stone-900 dark:text-stone-100">{{ $album->title }}</h2>
                                @if ($album->description)
                                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-300 line-clamp-2">{{ $album->description }}</p>
                                @endif
                            </div>
                        </a>
                        @can(\App\Permissions::GALLERY_MANAGE)
                            <div class="px-4 pb-4 -mt-2 flex justify-end text-xs">
                                <a href="{{ route('admin.gallery.albums.edit', $album) }}"
                                   class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 transition-colors"
                                   data-testid="manage-album-{{ $album->slug }}">
                                    Manage →
                                </a>
                            </div>
                        @endcan
                    </article>
                @endforeach
            </div>
        @endif
    </main>
</x-site-layout>
