<x-admin-layout title="Mango Gallery" active="gallery">
    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Mango Gallery</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Photo albums published at <a href="{{ route('gallery.index') }}" target="_blank" class="text-orange-700 hover:underline">/gallery</a>.</p>
        </div>
        <a href="{{ route('admin.gallery.albums.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm" data-testid="new-album">New album</a>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100" data-testid="flash-status">{{ session('status') }}</div>
    @endif

    <section data-testid="gallery-albums">
        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
            @if ($albums->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No albums yet. Create one to start uploading photos.</p>
            @else
                <ul class="divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach ($albums as $album)
                        <li class="px-4 py-3 flex items-center gap-4" data-testid="gallery-album-row-{{ $album->slug }}">
                            <div class="shrink-0 w-16 h-16 rounded-lg overflow-hidden bg-stone-100 dark:bg-stone-800 border border-stone-200 dark:border-stone-700">
                                @if ($album->coverPhoto)
                                    <img src="{{ $album->coverPhoto->thumbnailUrl() }}" alt="{{ $album->title }}" class="w-full h-full object-cover" loading="lazy">
                                @else
                                    <div class="w-full h-full flex items-center justify-center text-stone-400 text-2xl">📷</div>
                                @endif
                            </div>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-stone-900 dark:text-stone-100 truncate">{{ $album->title }}</p>
                                <p class="text-xs text-stone-500 dark:text-stone-400 truncate">
                                    <code class="font-mono">{{ $album->slug }}</code> · {{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }} · order {{ $album->display_order }}
                                </p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                @if ($album->published)
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">Published</span>
                                @else
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700">Draft</span>
                                @endif
                                <a href="{{ route('admin.gallery.albums.edit', $album) }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs">Manage →</a>
                            </div>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-admin-layout>
