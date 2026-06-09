@csrf
<div class="space-y-4">
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Name</label>
        <input name="name" type="text" required maxlength="120" value="{{ old('name', $designation->name) }}" class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" data-testid="designation-name">
        @error('name') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Level</label>
        <input name="level" type="number" min="0" max="100" value="{{ old('level', $designation->level ?? 0) }}" class="mt-1 block w-32 rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Higher = more senior; controls sort order on the hierarchy page.</p>
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Reports to</label>
        
        <select name="parent_id" class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100"
     data-testid="designation-parent">
            <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="">— top of hierarchy —</option>
            @foreach ($parentOptions ?? [] as $opt)
                <option class="bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100" value="{{ $opt->id }}" @selected((int) old('parent_id', $designation->parent_id) === $opt->id)>{{ $opt->name }}</option>
            @endforeach
        </select>
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Parent designation in the reporting chain. Users holding this designation will be visible to anyone holding the parent.</p>
        @error('parent_id') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
    </div>
    <div>
        <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Description</label>
        
        <textarea name="description" rows="2" maxlength="1000" class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100"
    >{{ old('description', $designation->description) }}</textarea>
    </div>
    <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium">Save designation</button>
</div>
