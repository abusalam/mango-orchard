@props([
    'action',
    'method' => 'POST',
    'title' => 'Are you sure?',
    'message' => null,
    'confirmLabel' => 'Confirm',
    'cancelLabel' => 'Cancel',
    'variant' => 'danger',
])

@php
    $confirmButtonClass = match ($variant) {
        'warning' => 'bg-amber-600 hover:bg-amber-700 text-white',
        default => 'bg-rose-700 hover:bg-rose-800 text-rose-50',
    };
    $spoofedMethod = strtoupper($method) !== 'POST';
@endphp

<form
    method="POST"
    action="{{ $action }}"
    x-data="{ open: false }"
    x-on:keydown.escape.window="open = false"
    {{ $attributes->merge(['class' => 'inline']) }}
>
    @csrf
    @if ($spoofedMethod)
        @method($method)
    @endif

    {{-- Default slot is the user-visible trigger (button / link styled as
         button). We intercept its click to open the modal instead of
         submitting the form directly — no `window.confirm()` involved. --}}
    <span x-on:click.prevent="open = true" class="contents">
        {{ $slot }}
    </span>

    <dialog
        x-ref="dialog"
        x-effect="open ? $refs.dialog.showModal() : $refs.dialog.close()"
        x-on:close="open = false"
        x-on:click.self="open = false"
        class="rounded-2xl border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-950 text-stone-900 dark:text-stone-100 p-0 w-full max-w-md shadow-2xl backdrop:bg-stone-900/50 backdrop:backdrop-blur-sm text-left"
    >
        <div class="p-6 sm:p-7 text-left">
            <h2 class="text-lg font-semibold tracking-tight">{{ $title }}</h2>
            @if ($message)
                <p class="mt-2 text-sm text-stone-600 dark:text-stone-300 leading-relaxed">{{ $message }}</p>
            @endif

            <div class="mt-6 flex justify-end gap-3">
                <button
                    type="button"
                    x-on:click="open = false"
                    class="inline-flex items-center px-4 py-2 rounded-full border border-stone-200 dark:border-stone-800 text-stone-700 dark:text-stone-300 font-medium hover:border-stone-400 transition-colors text-sm"
                    data-testid="confirm-form-cancel"
                >{{ $cancelLabel }}</button>

                <button
                    type="submit"
                    class="inline-flex items-center px-4 py-2 rounded-full font-medium transition-colors text-sm {{ $confirmButtonClass }}"
                    data-testid="confirm-form-confirm"
                >{{ $confirmLabel }}</button>
            </div>
        </div>
    </dialog>
</form>
