<x-admin-layout :title="$album->title.' — Mango Gallery'" active="gallery">
    <a href="{{ route('admin.gallery.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-3">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Albums
    </a>

    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $album->title }}</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm"><code>{{ $album->slug }}</code> · {{ $album->photos->count() }} {{ Str::plural('photo', $album->photos->count()) }}</p>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('gallery.show', $album) }}" target="_blank" class="inline-flex items-center px-3 py-1.5 rounded-full bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-100 hover:border-stone-400 text-sm">View public →</a>
            <x-confirm-form
                :action="route('admin.gallery.albums.destroy', $album)"
                method="DELETE"
                title="Delete this album?"
                body="Removes the album AND every photo (files on disk too). Cannot be undone."
                confirm-label="Delete">
                <button type="button" class="inline-flex items-center px-3 py-1.5 rounded-full bg-rose-50 dark:bg-rose-950 text-rose-900 dark:text-rose-200 border border-rose-200 dark:border-rose-800 hover:bg-rose-100 dark:hover:bg-rose-900 text-sm" data-testid="delete-album">Delete album</button>
            </x-confirm-form>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100" data-testid="flash-status">{{ session('status') }}</div>
    @endif

    @include('admin.gallery.albums._form')

    {{-- ============== Photo upload ============== --}}
    <section class="mt-8" data-testid="photo-upload-section">
        <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 mb-3">Add photos</h2>
        <form method="POST" action="{{ route('admin.gallery.photos.store', $album) }}" enctype="multipart/form-data"
              class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-5">
            @csrf
            <label for="photos" class="block">
                <span class="block text-sm font-medium text-stone-700 dark:text-stone-300">Select photos</span>
                <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">Shift-click or Cmd-click to pick multiple.</span>
                <input id="photos" name="photos[]" type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif"
                       class="mt-2 block w-full text-sm text-stone-700 dark:text-stone-300 file:mr-3 file:py-2 file:px-4 file:rounded-full file:border-0 file:bg-stone-900 file:text-amber-50 file:cursor-pointer file:hover:bg-stone-800"
                       required data-testid="photo-file-input">
            </label>
            <x-image-upload-guide
                dimensions="2000 × 1500 px or larger"
                aspect="any (4:3 fits the grid best)"
                formats="JPG, PNG, WebP, or GIF"
                maxSize="15 MB per file (up to 50 files at once)"
                note="Each upload is re-encoded as WebP at 1600px wide with a 600px thumbnail — start with a high-resolution source and the pipeline does the rest." />
            @error('photos') <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
            @error('photos.*') <p class="mt-2 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror

            <button type="submit" class="mt-4 inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="upload-photos">Upload</button>
        </form>
    </section>

    {{-- ============== Existing photos ============== --}}
    @if ($album->photos->isNotEmpty())
        <section class="mt-8" data-testid="photo-grid">
            <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 mb-3">Photos</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                @foreach ($album->photos as $photo)
                    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden" data-testid="photo-card-{{ $photo->id }}">
                        <div class="relative aspect-square bg-stone-100 dark:bg-stone-900">
                            <img src="{{ $photo->thumbnailUrl() }}" alt="{{ $photo->alt_text ?? $album->title }}"
                                 class="absolute inset-0 w-full h-full object-cover" loading="lazy">
                            @if ($album->cover_photo_id === $photo->id)
                                <span class="absolute top-2 left-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-amber-500 text-stone-900">COVER</span>
                            @endif
                        </div>
                        <div class="p-3 space-y-2">
                            <form method="POST" action="{{ route('admin.gallery.photos.update', [$album, $photo]) }}" class="space-y-2">
                                @csrf
                                @method('PUT')
                                <input type="text" name="caption" maxlength="500" placeholder="Caption (optional)"
                                       value="{{ $photo->caption }}"
                                       class="block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-xs">
                                <input type="text" name="alt_text" maxlength="255" placeholder="Alt text (a11y)"
                                       value="{{ $photo->alt_text }}"
                                       class="block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-xs">
                                <div class="flex items-center justify-between gap-2">
                                    <input type="number" name="position" min="0" value="{{ $photo->position }}"
                                           class="w-16 rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-xs">
                                    <button type="submit" class="px-2.5 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs">Save</button>
                                </div>
                            </form>

                            <div class="flex items-center justify-between gap-2 pt-2 border-t border-stone-100 dark:border-stone-800">
                                @if ($album->cover_photo_id !== $photo->id)
                                    <form method="POST" action="{{ route('admin.gallery.photos.set-cover', [$album, $photo]) }}" class="inline">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-xs text-orange-700 dark:text-amber-400 hover:underline">Set as cover</button>
                                    </form>
                                @else
                                    <span class="text-xs text-stone-400">Album cover</span>
                                @endif
                                <x-confirm-form
                                    :action="route('admin.gallery.photos.destroy', [$album, $photo])"
                                    method="DELETE"
                                    title="Delete this photo?"
                                    body="Removes the file from disk too. Cannot be undone."
                                    confirm-label="Delete">
                                    <button type="button" class="text-xs text-rose-700 dark:text-rose-400 hover:underline" data-testid="delete-photo-{{ $photo->id }}">Delete</button>
                                </x-confirm-form>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif
</x-admin-layout>
