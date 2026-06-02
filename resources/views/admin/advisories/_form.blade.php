@props(['advisory', 'varieties', 'selectedVarietyIds', 'action', 'method' => 'POST'])

@php
    $toDatetimeLocal = fn ($v) => $v ? \Illuminate\Support\Carbon::parse($v)->format('Y-m-d\TH:i') : '';
@endphp

<form method="POST" action="{{ $action }}" class="space-y-6">
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    <div>
        <label for="title" class="block text-sm font-medium text-stone-800">Title</label>
        <input type="text" name="title" id="title" required maxlength="200"
               value="{{ old('title', $advisory->title) }}"
               placeholder="e.g. Pre-monsoon fungal-spray window opens this week"
               class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
        @error('title') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div>
        <label for="body" class="block text-sm font-medium text-stone-800">Body</label>
        <textarea name="body" id="body" rows="8" required maxlength="10000"
                  placeholder="What to watch for, what to do, when to act."
                  class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">{{ old('body', $advisory->body) }}</textarea>
        @error('body') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="category" class="block text-sm font-medium text-stone-800">Category</label>
            <select name="category" id="category" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (\App\Models\Advisory::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('category', $advisory->category) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('category') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="severity" class="block text-sm font-medium text-stone-800">Severity</label>
            <select name="severity" id="severity" required
                    class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
                @foreach (\App\Models\Advisory::SEVERITIES as $value => $label)
                    <option value="{{ $value }}" @selected(old('severity', $advisory->severity) === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('severity') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label class="block text-sm font-medium text-stone-800">Target varieties <span class="text-stone-400 font-normal">(leave empty to apply to every variety)</span></label>
        <div class="mt-2 grid sm:grid-cols-2 gap-2 max-h-64 overflow-y-auto rounded-lg border border-stone-200 p-3 bg-stone-50">
            @php($selected = collect(old('mango_variety_ids', $selectedVarietyIds))->map(fn ($i) => (int) $i)->all())
            @foreach ($varieties as $variety)
                <label class="flex items-center gap-2 text-sm text-stone-700">
                    <input type="checkbox" name="mango_variety_ids[]" value="{{ $variety->id }}"
                           @checked(in_array($variety->id, $selected, true))
                           class="rounded text-orange-500 focus:ring-orange-400">
                    {{ $variety->name }}
                </label>
            @endforeach
        </div>
        @error('mango_variety_ids') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label for="issued_at" class="block text-sm font-medium text-stone-800">Issued at <span class="text-stone-400 font-normal">(blank = now, on publish)</span></label>
            <input type="datetime-local" name="issued_at" id="issued_at"
                   value="{{ old('issued_at', $toDatetimeLocal($advisory->issued_at)) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('issued_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label for="expires_at" class="block text-sm font-medium text-stone-800">Expires at <span class="text-stone-400 font-normal">(optional)</span></label>
            <input type="datetime-local" name="expires_at" id="expires_at"
                   value="{{ old('expires_at', $toDatetimeLocal($advisory->expires_at)) }}"
                   class="mt-1 block w-full rounded-lg border-stone-300 shadow-sm focus:border-orange-400 focus:ring-orange-400">
            @error('expires_at') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>

    <div>
        <label for="image" class="block text-sm font-medium text-stone-800">Illustrative photo <span class="text-stone-400 font-normal">(optional, max 5 MB)</span></label>
        @if ($advisory->image_path)
            <div class="mt-2 flex items-start gap-4">
                <img src="{{ $advisory->image_url }}" alt="Current photo for {{ $advisory->title }}"
                     class="w-32 h-20 object-cover rounded-lg border border-stone-200" data-testid="advisory-current-image">
                <label class="inline-flex items-center gap-2 text-sm text-rose-700 cursor-pointer">
                    <input type="checkbox" name="remove_image" value="1" class="rounded text-rose-600 focus:ring-rose-400">
                    Remove current photo
                </label>
            </div>
        @endif
        {{-- Form defaults to urlencoded; promote to multipart only when a
             file is actually selected so the upload transmits. --}}
        <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
               onchange="this.files.length && (this.form.enctype = 'multipart/form-data')"
               class="mt-2 block w-full text-sm text-stone-600 file:mr-3 file:py-1.5 file:px-3 file:rounded-full file:border-0 file:text-xs file:font-medium file:bg-stone-900 file:text-amber-50 hover:file:bg-stone-800"
               data-testid="advisory-image-input">
        <p class="mt-1 text-xs text-stone-500">JPEG, PNG, or WebP. Helpful for pest identification or technique illustration.</p>
        @error('image') <p class="mt-1 text-sm text-rose-600">{{ $message }}</p> @enderror
    </div>

    <label class="flex items-start gap-3 p-4 rounded-xl border border-stone-200 cursor-pointer hover:border-orange-300 has-[:checked]:border-orange-500 has-[:checked]:bg-orange-50 transition-colors">
        <input type="hidden" name="published" value="0">
        <input type="checkbox" name="published" value="1" @checked(old('published', $advisory->published))
               class="mt-1 rounded text-orange-500 focus:ring-orange-400">
        <span>
            <span class="block text-sm font-medium text-stone-800">Publish</span>
            <span class="block text-xs text-stone-500 mt-0.5">When on, this advisory appears on the public /advisories feed and the dashboard. Off = draft, only visible to advisors.</span>
        </span>
    </label>

    <div class="flex items-center gap-3 pt-4 border-t border-stone-100">
        <button type="submit" class="inline-flex items-center px-5 py-2.5 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
            {{ $slot ?? 'Save advisory' }}
        </button>
        <a href="{{ route('admin.advisories.index') }}" class="text-sm text-stone-600 hover:text-stone-900">Cancel</a>
    </div>
</form>
