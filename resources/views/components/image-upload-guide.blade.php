@props([
    'dimensions' => '1200 × 900 px',
    'aspect' => '4:3',
    'maxSize' => '5 MB',
    'formats' => 'JPG, PNG, or WebP',
    'note' => null,
])

{{-- Compact resolution / format guide shown next to file-upload inputs.
     Pass props to override any field per-form. --}}
<div {{ $attributes->merge(['class' => 'mt-2 rounded-lg bg-amber-50 dark:bg-stone-900 border border-amber-200 dark:border-stone-800 px-3 py-2 text-xs text-stone-700 dark:text-stone-300']) }} data-testid="image-upload-guide">
    <p class="font-medium text-stone-900 dark:text-stone-100">Image guidelines</p>
    <ul class="mt-1 space-y-0.5 list-none">
        <li><span class="text-stone-500 dark:text-stone-400">Recommended size:</span> <strong>{{ $dimensions }}</strong> <span class="text-stone-500 dark:text-stone-400">({{ $aspect }} aspect ratio)</span></li>
        <li><span class="text-stone-500 dark:text-stone-400">Format:</span> <strong>{{ $formats }}</strong></li>
        <li><span class="text-stone-500 dark:text-stone-400">Max file size:</span> <strong>{{ $maxSize }}</strong></li>
        @if ($note)
            <li class="mt-1 text-stone-600 dark:text-stone-400 italic">{{ $note }}</li>
        @endif
    </ul>
</div>
