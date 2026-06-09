{{--
    Polymorphic attachments panel. Drop into any view that has either a
    Scheme or Task instance. Caller passes `$attachable` and `$uploadRoute`.

        @include('scheme-monitoring::_attachments', [
            'attachable' => $scheme,
            'uploadRoute' => route('monitoring.schemes.attachments.store', $scheme),
        ])
--}}
@props(['attachable', 'uploadRoute'])

{{-- Top spacing is left to the caller so the panel can either sit
     standalone or be nested inside a parent container with shared padding. --}}
<div data-testid="attachments-panel">
    <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100">Attachments</h2>

    @if ($attachable->attachments->isEmpty())
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">No files attached yet.</p>
    @else
        <ul class="mt-3 divide-y divide-stone-100 dark:divide-stone-800 border border-stone-200 dark:border-stone-800 rounded-xl overflow-hidden bg-white dark:bg-stone-950">
            @foreach ($attachable->attachments as $attachment)
                <li class="flex items-center gap-3 px-4 py-3" data-testid="attachment-row-{{ $attachment->id }}">
                    <svg class="w-5 h-5 shrink-0 text-stone-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M21.44 11.05l-9.19 9.19a6 6 0 0 1-8.49-8.49l9.19-9.19a4 4 0 0 1 5.66 5.66L9.41 17.34a2 2 0 0 1-2.83-2.83L15.07 6"/>
                    </svg>
                    <div class="min-w-0 flex-1">
                        <a href="{{ $attachment->url() }}" target="_blank" rel="noopener" class="font-medium text-stone-900 dark:text-stone-100 hover:underline break-words">
                            {{ $attachment->original_name }}
                        </a>
                        <p class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">
                            {{ $attachment->humanSize() }} ·
                            uploaded {{ $attachment->created_at->diffForHumans() }}
                            @if ($attachment->uploader)
                                · by {{ $attachment->uploader->name }}
                            @endif
                        </p>
                    </div>
                    <form method="POST" action="{{ route('monitoring.attachments.destroy', $attachment) }}" class="shrink-0">
                        @csrf @method('DELETE')
                        <button type="submit" class="text-xs text-rose-700 dark:text-rose-400 hover:underline" data-testid="attachment-delete-{{ $attachment->id }}">Remove</button>
                    </form>
                </li>
            @endforeach
        </ul>
    @endif

    <form method="POST" action="{{ $uploadRoute }}" enctype="multipart/form-data" class="mt-4 flex flex-wrap items-end gap-3">
        @csrf
        <div class="flex-1 min-w-[14rem]">
            <label class="block text-sm font-medium text-stone-700 dark:text-stone-300">Add an attachment</label>
            <input
                type="file"
                name="file"
                required
                class="mt-1 block w-full text-sm text-stone-700 dark:text-stone-300 file:mr-3 file:px-3 file:py-1.5 file:rounded-full file:border-0 file:bg-stone-900 file:text-amber-50 file:text-xs file:font-medium hover:file:bg-stone-800"
                data-testid="attachment-file-input"
            >
            <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Up to 10 MB per file.</p>
            @error('file') <p class="mt-1 text-xs text-rose-600 dark:text-rose-400">{{ $message }}</p> @enderror
        </div>
        <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 text-sm font-medium" data-testid="attachment-upload-button">
            Upload
        </button>
    </form>
</div>
