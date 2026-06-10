@props([
    'title' => null,
    'step' => null,
    'totalSteps' => 3,
    'wide' => false,
])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-form-autofill-meta />

        <title>{{ $title ? $title.' — Aamar Malda' : config('app.name', 'Aamar Malda') }}</title>

        <link rel="icon" type="image/webp" href="/images/LOGO-Square.webp">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=optional" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-theme-bootstrap />
    </head>
    <body class="font-sans text-stone-900 dark:text-stone-100 antialiased">
        <x-readonly-banner />
        <x-dev-banner />
        <x-impersonation-banner />
        @if ($step)
            {{-- Onboarding chrome: branded header bar + step indicator. --}}
            <div class="min-h-screen flex flex-col bg-amber-50 dark:bg-stone-900">
                <header class="border-b border-amber-200/60 dark:border-stone-800 bg-amber-50/80 dark:bg-stone-900/80 backdrop-blur">
                    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                        <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                            <img src="/images/LOGO-Square.webp" alt="Aamar Malda" class="inline-block w-7 h-7 rounded-full object-cover">
                            <span class="text-stone-900 dark:text-stone-100">Aamar Malda</span>
                        </a>
                        @auth
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100">Log out</button>
                            </form>
                        @endauth
                    </div>
                </header>

                <main class="flex-1 mx-auto w-full max-w-3xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14">
                    <nav aria-label="Onboarding progress" class="mb-10">
                        <ol class="grid grid-cols-3 gap-3 text-xs sm:text-sm">
                            @foreach (['account' => 'Account', 'profile' => 'Profile', 'preferences' => 'Preferences'] as $key => $label)
                                @php
                                    $stepIndex = array_search($key, ['account', 'profile', 'preferences'], true) + 1;
                                    $currentIndex = array_search($step, ['account', 'profile', 'preferences'], true) + 1;
                                    $isCurrent = $stepIndex === $currentIndex;
                                    $isDone = $stepIndex < $currentIndex;
                                @endphp
                                <li>
                                    <div data-onboarding-step="{{ $key }}"
                                         @class([
                                            'flex items-center gap-2 px-3 py-2 rounded-xl border',
                                            'bg-stone-900 text-amber-50 border-stone-900' => $isCurrent,
                                            'bg-emerald-50 text-emerald-900 border-emerald-200' => $isDone,
                                            'bg-white dark:bg-stone-950 text-stone-500 dark:text-stone-400 border-stone-200 dark:border-stone-800' => ! $isCurrent && ! $isDone,
                                         ])>
                                        <span @class([
                                            'inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-semibold',
                                            'bg-amber-300 text-stone-900' => $isCurrent,
                                            'bg-emerald-600 text-white' => $isDone,
                                            'bg-stone-100 dark:bg-stone-800 text-stone-500 dark:text-stone-400' => ! $isCurrent && ! $isDone,
                                        ])>
                                            {{ $isDone ? '✓' : $stepIndex }}
                                        </span>
                                        <span>{{ $label }}</span>
                                    </div>
                                </li>
                            @endforeach
                        </ol>
                    </nav>

                    {{ $slot }}
                </main>
            </div>
        @else
            {{-- Auth chrome: standalone logo over a centered card. --}}
            <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-amber-50 dark:bg-stone-900">
                <div>
                    <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight text-stone-900 dark:text-stone-100">
                        <img src="/images/LOGO-Square.webp" alt="Aamar Malda" class="inline-block w-10 h-10 rounded-full object-cover">
                        <span class="text-lg">Aamar Malda</span>
                    </a>
                </div>

                <div class="w-full {{ $wide ? 'sm:max-w-3xl' : 'sm:max-w-md' }} mt-6 px-6 py-4 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl overflow-hidden">
                    {{ $slot }}
                </div>
            </div>
        @endif
        <x-cookie-banner />
    </body>
</html>
