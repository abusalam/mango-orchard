@props(['event'])

@php
    $context = $event->context ?? [];
    $impersonatorId = $context['impersonator_id'] ?? null;
    $impersonatorEmail = $context['impersonator_email'] ?? null;
@endphp

@if ($impersonatorId)
    <span
        class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-[10px] font-medium bg-rose-100 text-rose-900 border border-rose-200 align-middle"
        title="Impersonated by {{ $impersonatorEmail ?? 'user #'.$impersonatorId }}"
        data-testid="telemetry-impersonated-tag"
    >
        <svg class="w-2.5 h-2.5" viewBox="0 0 12 12" fill="currentColor" aria-hidden="true">
            <path d="M6 1a2.5 2.5 0 1 0 0 5 2.5 2.5 0 0 0 0-5zM1 11c0-2.5 2.2-4 5-4s5 1.5 5 4v.5H1V11z"/>
        </svg>
        impersonated
    </span>
@endif
