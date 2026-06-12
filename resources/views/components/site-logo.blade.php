@props([
    // nav (28px) | auth (40px) | footer (160→192px) | hero (224→288px)
    'size' => 'nav',
])

@php
    $logoUrl = app(\App\Settings\Settings::class)->siteLogoUrl();

    [$sizeClasses, $textClasses] = match ($size) {
        'auth' => ['w-10 h-10', 'text-sm'],
        'footer' => ['w-40 h-40 sm:w-48 sm:h-48', 'text-5xl'],
        'hero' => ['w-56 h-56 sm:w-64 sm:h-64 lg:w-72 lg:h-72', 'text-7xl'],
        default => ['w-7 h-7', 'text-[10px]'],
    };

    // Generated-monogram fallback: first letter of the first two words of
    // the app name over the brand gradient — a fresh install has a usable
    // "logo" before anything is uploaded.
    $initials = collect(explode(' ', trim((string) config('app.name'))))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
@endphp

@if ($logoUrl)
    <img src="{{ $logoUrl }}" alt="{{ config('app.name') }}"
         {{ $attributes->merge(['class' => "{$sizeClasses} rounded-full object-cover"]) }}
         data-testid="site-logo">
@else
    <span {{ $attributes->merge(['class' => "{$sizeClasses} {$textClasses} inline-flex items-center justify-center rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 text-white font-bold tracking-tight shadow-inner ring-1 ring-orange-700/20 select-none"]) }}
          aria-hidden="true" data-testid="site-logo-monogram">{{ $initials !== '' ? $initials : '•' }}</span>
@endif
