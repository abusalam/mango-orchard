@props([
    'user',
    'size' => 'md', // xs (24px) | sm (32px) | md (36px) | lg (64px) | xl (96px)
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'w-6 h-6 text-[9px]',
        'sm' => 'w-8 h-8 text-[11px]',
        'lg' => 'w-16 h-16 text-xl',
        'xl' => 'w-24 h-24 text-3xl',
        default => 'w-9 h-9 text-xs',
    };
    // Initials fallback: first letter of the first two words.
    $initials = collect(explode(' ', trim($user->name)))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

@if ($user->avatar_url)
    <img src="{{ $user->avatar_url }}" alt="{{ $user->name }}"
         {{ $attributes->merge(['class' => "{$sizeClasses} rounded-full object-cover border border-stone-200 dark:border-stone-700"]) }}
         loading="lazy" decoding="async" data-testid="user-avatar">
@else
    <span {{ $attributes->merge(['class' => "{$sizeClasses} inline-flex items-center justify-center rounded-full bg-gradient-to-br from-amber-400 to-orange-500 text-white font-semibold select-none"]) }}
          aria-hidden="true" data-testid="user-avatar-initials">{{ $initials !== '' ? $initials : '?' }}</span>
@endif
