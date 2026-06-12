@props(['variety', 'action', 'method' => 'POST'])

@php
    $tagsValue = old('tags', is_array($variety->tags ?? null) ? implode(', ', $variety->tags) : '');
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="name" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Name</label>
        <input type="text" name="name" id="name" required maxlength="120"
               value="{{ old('name', $variety->name) }}"
               class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('name') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="origin" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Origin</label>
        <input type="text" name="origin" id="origin" required maxlength="120"
               value="{{ old('origin', $variety->origin) }}"
               placeholder="e.g. Ratnagiri, India"
               class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('origin') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="sm:col-span-3">
            <label for="season" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Season label</label>
            <input type="text" name="season" id="season" required maxlength="60"
                   value="{{ old('season', $variety->season) }}"
                   placeholder="e.g. Apr – Jun"
                   class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('season') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="season_start" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Season starts (month)</label>
            
        <select name="season_start" id="season_start" required
                    class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >
                @foreach (range(1, 12) as $m)
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $m }}" @selected((int) old('season_start', $variety->season_start) === $m)>
                        {{ \DateTime::createFromFormat('!m', (string) $m)->format('F') }}
                    </option>
                @endforeach
            </select>
            @error('season_start') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="season_end" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Season ends (month)</label>
            
        <select name="season_end" id="season_end" required
                    class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >
                @foreach (range(1, 12) as $m)
                    <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $m }}" @selected((int) old('season_end', $variety->season_end) === $m)>
                        {{ \DateTime::createFromFormat('!m', (string) $m)->format('F') }}
                    </option>
                @endforeach
            </select>
            @error('season_end') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="flavor" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Flavor / tasting notes</label>
        
        <textarea name="flavor" id="flavor" rows="4" required maxlength="1000"
                  class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >{{ old('flavor', $variety->flavor) }}</textarea>
        @error('flavor') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="tags" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Tags <span class="text-stone-400 font-normal">(comma-separated)</span></label>
        
        <input type="text" name="tags" id="tags" maxlength="255"
               value="{{ $tagsValue }}"
               placeholder="Premium, Aromatic, Low fiber"
               class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >
        @error('tags') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="theme" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Color theme</label>
        
        <select name="theme" id="theme" required
                class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 shadow-sm focus:border-orange-400 focus:ring-orange-400"
    >
            @foreach (\App\Modules\MangoOrchard\Models\MangoVariety::THEMES as $key => $theme)
                <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $key }}" @selected(old('theme', $variety->theme) === $key)>{{ $theme['label'] }}</option>
            @endforeach
        </select>
        @error('theme') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-stone-800 dark:text-stone-200">Variety photo</label>
        @if ($variety->image_url)
            <div class="mt-2 flex items-start gap-4">
                <img src="{{ $variety->image_url }}" alt="{{ $variety->name }}"
                     class="w-32 h-24 object-cover rounded-lg border border-stone-200 dark:border-stone-800" data-testid="current-variety-image">
                <label class="inline-flex items-center gap-2 text-sm text-stone-700 dark:text-stone-300">
                    <input type="checkbox" name="remove_image" value="1" class="rounded text-rose-500 focus:ring-rose-400" data-testid="remove-variety-image">
                    <span>Remove current photo</span>
                </label>
            </div>
        @endif
        {{-- Form defaults to urlencoded; promote to multipart only when a
             file is actually selected so the upload transmits. --}}
        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
               onchange="this.files.length && (this.form.enctype = 'multipart/form-data')"
               class="mt-2 block w-full text-sm text-stone-600 dark:text-stone-300 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-medium file:bg-stone-900 file:text-amber-50 hover:file:bg-stone-800"
               data-max-bytes="{{ \App\Support\UploadLimits::effectiveBytes(5120) }}"
               data-testid="variety-image-input">
        <x-image-upload-guide
            dimensions="1200 × 900 px"
            aspect="4:3"
            :max-kb="5120"
            note="A single ripe fruit on a clean background reads best on cards; full-tree shots work for the detail page. The color theme is used as a fallback when no photo is uploaded." />
        @error('image') <p class="mt-1 text-sm text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100 dark:border-stone-800">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save variety' }}
        </button>
        <a href="{{ route('varieties.index') }}" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100">Cancel</a>
    </div>
</form>
