@props(['title', 'step', 'totalSteps' => 3])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-form-autofill-meta />

        <title>{{ $title }} — Mango Orchard</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-amber-50 text-stone-900 antialiased min-h-screen flex flex-col">
        <x-impersonation-banner />
        <header class="border-b border-amber-200/60 bg-amber-50/80 backdrop-blur">
            <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 shadow-inner ring-1 ring-orange-700/20"></span>
                    <span class="text-stone-900">Mango Orchard</span>
                </a>
                <form method="POST" action="{{ route('logout') }}" class="inline">
                    @csrf
                    <button type="submit" class="text-sm text-stone-600 hover:text-stone-900">Log out</button>
                </form>
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
                                    'bg-white text-stone-500 border-stone-200' => ! $isCurrent && ! $isDone,
                                 ])>
                                <span @class([
                                    'inline-flex items-center justify-center w-5 h-5 rounded-full text-[10px] font-semibold',
                                    'bg-amber-300 text-stone-900' => $isCurrent,
                                    'bg-emerald-600 text-white' => $isDone,
                                    'bg-stone-100 text-stone-500' => ! $isCurrent && ! $isDone,
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
    </body>
</html>
