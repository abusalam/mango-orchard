@php
    $isEditing = $album->exists;
    $action = $isEditing
        ? route('admin.gallery.albums.update', $album)
        : route('admin.gallery.albums.store');
@endphp

<form method="POST" action="{{ $action }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5" data-testid="album-form">
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div class="grid sm:grid-cols-3 gap-4">
        <div>
            <label for="slug" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Slug</label>
            <input id="slug" name="slug" type="text" required pattern="[a-z0-9-]+"
                   value="{{ old('slug', $album->slug ?? '') }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-mono text-sm">
            @error('slug') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="display_order" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Display order</label>
            <input id="display_order" name="display_order" type="number" min="0"
                   value="{{ old('display_order', $album->display_order ?? 0) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        </div>
        <label class="flex items-start gap-3 p-3 rounded-xl border border-stone-200 dark:border-stone-700 cursor-pointer hover:border-emerald-300 has-[:checked]:border-emerald-500 has-[:checked]:bg-emerald-50 dark:has-[:checked]:bg-emerald-950 transition-colors">
            <input type="hidden" name="published" value="0">
            <input type="checkbox" name="published" value="1" @checked(old('published', $album->published ?? true)) class="mt-1 rounded text-emerald-500 focus:ring-emerald-400" data-testid="published-toggle">
            <span class="text-sm">
                <span class="block font-medium text-stone-900 dark:text-stone-100">Published</span>
                <span class="block text-xs text-stone-500 dark:text-stone-400 mt-0.5">When off, hidden from /gallery.</span>
            </span>
        </label>
    </div>

    <div>
        <label for="title" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Title</label>
        <input id="title" name="title" type="text" required value="{{ old('title', $album->title ?? '') }}"
               class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
    </div>

    <div>
        <label for="description" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Description</label>
        <textarea id="description" name="description" rows="3" maxlength="2000"
                  class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm">{{ old('description', $album->description ?? '') }}</textarea>
    </div>

    <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-album">{{ $isEditing ? 'Save album' : 'Create album' }}</button>
        <a href="{{ route('admin.gallery.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
    </div>
</form>
