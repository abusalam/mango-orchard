@props([
    'dimensions' => '1200 × 900 px',
    'aspect' => '4:3',
    // App-level validation limit in KB. When set, the displayed maximum is
    // min(this, php.ini upload_max_filesize / post_max_size) so the guide
    // never promises more than the server will actually accept.
    'maxKb' => null,
    // Legacy free-text fallback for call sites without a numeric limit.
    'maxSize' => '5 MB',
    'formats' => 'JPG, PNG, or WebP',
    'note' => null,
])

@php
    $effective = $maxKb !== null ? \App\Support\UploadLimits::effectiveBytes((int) $maxKb) : null;
    $serverConstrains = $maxKb !== null && \App\Support\UploadLimits::serverConstrains((int) $maxKb);
@endphp

{{-- Compact resolution / format guide shown next to file-upload inputs.
     Pass props to override any field per-form. --}}
<div {{ $attributes->merge(['class' => 'mt-2 rounded-lg bg-amber-50 dark:bg-stone-900 border border-amber-200 dark:border-stone-800 px-3 py-2 text-xs text-stone-700 dark:text-stone-300']) }} data-testid="image-upload-guide">
    <p class="font-medium text-stone-900 dark:text-stone-100">Image guidelines</p>
    <ul class="mt-1 space-y-0.5 list-none">
        <li><span class="text-stone-500 dark:text-stone-400">Recommended size:</span> <strong>{{ $dimensions }}</strong> <span class="text-stone-500 dark:text-stone-400">({{ $aspect }} aspect ratio)</span></li>
        <li><span class="text-stone-500 dark:text-stone-400">Format:</span> <strong>{{ $formats }}</strong></li>
        <li>
            <span class="text-stone-500 dark:text-stone-400">Max file size:</span>
            <strong data-testid="upload-guide-max">{{ $effective !== null ? \App\Support\UploadLimits::format($effective) : $maxSize }}</strong>
            @if ($serverConstrains)
                <span class="text-stone-500 dark:text-stone-400">(limited by server configuration)</span>
            @endif
        </li>
        @if ($note)
            <li class="mt-1 text-stone-600 dark:text-stone-400 italic">{{ $note }}</li>
        @endif
    </ul>
</div>
