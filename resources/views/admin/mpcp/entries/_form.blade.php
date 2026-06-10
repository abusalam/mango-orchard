@php
    $isEditing = $entry->exists;
    $action = $isEditing
        ? route('admin.mpcp.entries.update', [$section, $entry])
        : route('admin.mpcp.entries.store', $section);
@endphp

<form method="POST" action="{{ $action }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6 space-y-5" data-testid="mpcp-entry-form">
    @csrf
    @if ($isEditing) @method('PUT') @endif

    <div>
        <label for="position" class="block text-sm font-medium text-stone-700 dark:text-stone-300">Position</label>
        <input id="position" name="position" type="number" min="0" value="{{ old('position', $entry->position ?? '') }}"
               class="mt-1 block w-32 rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
        <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Leave blank on new entries to append at the end.</p>
    </div>

    @foreach ($section->columns as $col)
        @php
            $key = $col['key'];
            $name = "data[{$key}]";
            $id = "data_{$key}";
            $value = old("data.{$key}", $entry->data[$key] ?? '');
            $isLong = $col['type'] === 'long_text';
            $inputType = match ($col['type']) {
                'email' => 'email',
                'tel' => 'tel',
                default => 'text',
            };
        @endphp
        <div>
            <label for="{{ $id }}" class="block text-sm font-medium text-stone-700 dark:text-stone-300">
                <span>{{ $col['label_en'] }}</span>
                @if (! empty($col['label_bn']))
                    <span class="text-stone-500 dark:text-stone-400 text-xs font-normal">{{ $col['label_bn'] }}</span>
                @endif
                <code class="ml-1 text-[10px] text-stone-400">{{ $col['type'] }}</code>
            </label>
            @if ($isLong)
                <textarea id="{{ $id }}" name="{{ $name }}" rows="6"
                          class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 text-sm @if ($col['key'] === 'markdown') font-mono text-xs @endif">{{ $value }}</textarea>
            @else
                <input id="{{ $id }}" name="{{ $name }}" type="{{ $inputType }}" value="{{ $value }}"
                       class="mt-1 block w-full rounded-lg border-stone-300 dark:border-stone-700 bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100">
            @endif
            @error("data.{$key}") <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
    @endforeach

    <div class="flex items-center gap-3 pt-2 border-t border-stone-100 dark:border-stone-800">
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm font-medium" data-testid="save-entry">{{ $isEditing ? 'Save entry' : 'Add entry' }}</button>
        <a href="{{ route('admin.mpcp.entries.index', $section) }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:hover:text-stone-100">Cancel</a>
    </div>
</form>
