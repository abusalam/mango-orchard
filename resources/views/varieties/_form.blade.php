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
        <label for="name" class="block text-sm font-medium text-stone-800">Name</label>
        <input type="text" name="name" id="name" required maxlength="120"
               value="{{ old('name', $variety->name) }}"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('name') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="origin" class="block text-sm font-medium text-stone-800">Origin</label>
        <input type="text" name="origin" id="origin" required maxlength="120"
               value="{{ old('origin', $variety->origin) }}"
               placeholder="e.g. Ratnagiri, India"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('origin') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-3 gap-4">
        <div class="sm:col-span-3">
            <label for="season" class="block text-sm font-medium text-stone-800">Season label</label>
            <input type="text" name="season" id="season" required maxlength="60"
                   value="{{ old('season', $variety->season) }}"
                   placeholder="e.g. Apr – Jun"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('season') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="season_start" class="block text-sm font-medium text-stone-800">Season starts (month)</label>
            <select name="season_start" id="season_start" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected((int) old('season_start', $variety->season_start) === $m)>
                        {{ \DateTime::createFromFormat('!m', (string) $m)->format('F') }}
                    </option>
                @endforeach
            </select>
            @error('season_start') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="season_end" class="block text-sm font-medium text-stone-800">Season ends (month)</label>
            <select name="season_end" id="season_end" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (range(1, 12) as $m)
                    <option value="{{ $m }}" @selected((int) old('season_end', $variety->season_end) === $m)>
                        {{ \DateTime::createFromFormat('!m', (string) $m)->format('F') }}
                    </option>
                @endforeach
            </select>
            @error('season_end') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="flavor" class="block text-sm font-medium text-stone-800">Flavor / tasting notes</label>
        <textarea name="flavor" id="flavor" rows="4" required maxlength="1000"
                  class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">{{ old('flavor', $variety->flavor) }}</textarea>
        @error('flavor') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="tags" class="block text-sm font-medium text-stone-800">Tags <span class="text-stone-400 font-normal">(comma-separated)</span></label>
        <input type="text" name="tags" id="tags" maxlength="255"
               value="{{ $tagsValue }}"
               placeholder="Premium, Aromatic, Low fiber"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('tags') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="theme" class="block text-sm font-medium text-stone-800">Color theme</label>
        <select name="theme" id="theme" required
                class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @foreach (\App\Modules\MangoOrchard\Models\MangoVariety::THEMES as $key => $theme)
                <option value="{{ $key }}" @selected(old('theme', $variety->theme) === $key)>{{ $theme['label'] }}</option>
            @endforeach
        </select>
        @error('theme') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save variety' }}
        </button>
        <a href="{{ route('varieties.index') }}" class="text-sm text-stone-600 hover:text-stone-900">Cancel</a>
    </div>
</form>
