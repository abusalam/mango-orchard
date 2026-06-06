@csrf
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-stone-700">Name</label>
        <input name="name" type="text" required maxlength="120" value="{{ old('name', $designation->name) }}" class="mt-1 block w-full rounded-lg border-stone-300" data-testid="designation-name">
        @error('name') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Level</label>
        <input name="level" type="number" min="0" max="100" value="{{ old('level', $designation->level ?? 0) }}" class="mt-1 block w-32 rounded-lg border-stone-300">
        <p class="mt-1 text-xs text-stone-500">Higher = more senior; controls sort order on the hierarchy page.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700">Description</label>
        <textarea name="description" rows="2" maxlength="1000" class="mt-1 block w-full rounded-lg border-stone-300">{{ old('description', $designation->description) }}</textarea>
    </div>
    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium">Save designation</button>
</div>
