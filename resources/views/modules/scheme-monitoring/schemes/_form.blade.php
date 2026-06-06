@csrf
<div class="space-y-4">
    <div class="grid sm:grid-cols-[1fr_8rem] gap-4">
        <div>
            <label class="block text-sm font-medium text-stone-700">Name</label>
            <input name="name" type="text" value="{{ old('name', $scheme->name) }}" required maxlength="255"
                class="mt-1 block w-full rounded-lg border-stone-300 focus:border-orange-400 focus:ring-orange-400"
                data-testid="scheme-name">
            @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-stone-700">Abbreviation</label>
            <input name="abbreviation" type="text" value="{{ old('abbreviation', $scheme->abbreviation) }}" maxlength="12"
                placeholder="auto"
                class="mt-1 block w-full rounded-lg border-stone-300 uppercase tracking-wider focus:border-orange-400 focus:ring-orange-400"
                data-testid="scheme-abbreviation">
            <p class="mt-1 text-[10px] text-stone-500">Shown as a chip on the dashboard. Leave blank to auto-generate from the name.</p>
            @error('abbreviation') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Description</label>
        <textarea name="description" rows="3" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('description', $scheme->description) }}</textarea>
        @error('description') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div class="grid sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-sm font-medium text-stone-700">Start date</label>
            <input name="start_date" type="date" value="{{ old('start_date', optional($scheme->start_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('start_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="block text-sm font-medium text-stone-700">End date</label>
            <input name="end_date" type="date" value="{{ old('end_date', optional($scheme->end_date)->format('Y-m-d')) }}" class="mt-1 block w-full rounded-lg border-stone-300">
            @error('end_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Status</label>
        <select name="status" class="mt-1 block w-full rounded-lg border-stone-300">
            @foreach (\App\Modules\SchemeMonitoring\Models\Scheme::STATUSES as $v => $label)
                <option value="{{ $v }}" @selected(old('status', $scheme->status ?? 'active') === $v)>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <button type="submit" class="inline-flex items-center px-5 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium">
        Save scheme
    </button>
</div>
