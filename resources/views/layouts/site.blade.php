<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-form-autofill-meta />

        <title>{{ $title ?? 'Mango Orchard' }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-amber-50 text-stone-900 antialiased min-h-screen flex flex-col">
        <x-readonly-banner />
        <x-impersonation-banner />
        <header class="sticky top-0 z-30 backdrop-blur bg-amber-50/80 border-b border-amber-200/60" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 shadow-inner ring-1 ring-orange-700/20"></span>
                    <span class="text-stone-900">Mango Orchard</span>
                </a>

                <nav class="hidden lg:flex items-center gap-5 text-sm text-stone-700">
                    <a href="{{ route('home') }}" class="hover:text-orange-700 transition-colors">Home</a>
                    <a href="{{ route('varieties.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('varieties.*') ? 'text-orange-700' : '' }}">All varieties</a>
                    <a href="{{ route('listings.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('listings.*') ? 'text-orange-700' : '' }}">Marketplace</a>
                    <a href="{{ route('events.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('events.*') ? 'text-orange-700' : '' }}">Training</a>
                    <a href="{{ route('advisories.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('advisories.*') ? 'text-orange-700' : '' }}">Advisories</a>

                    @guest
                        <a href="{{ route('login') }}" class="hover:text-orange-700 transition-colors">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 transition-colors text-xs">Get started</a>
                    @else
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium">Finish onboarding</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-orange-700 transition-colors">Log out</button>
                            </form>
                        @else
                            @can(\App\Permissions::LISTINGS_MANAGE)
                                <a href="{{ route('my.listings.create') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium">List your harvest</a>
                            @endcan
                            <div class="relative" x-data="{ menu: false }" @click.away="menu = false">
                                <button @click="menu = !menu" type="button" class="flex items-center gap-2 hover:text-orange-700 transition-colors">
                                    <span>{{ auth()->user()->name }}</span>
                                    <x-user-role-badge :user="auth()->user()" />
                                    <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div x-show="menu" x-cloak x-transition class="absolute right-0 mt-2 w-52 bg-white border border-stone-200 rounded-xl shadow-lg overflow-hidden text-stone-700 py-1">
                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">Dashboard</a>
                                    <a href="{{ route('my.listings.index') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">My listings</a>
                                    @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::VARIETIES_MANAGE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE])
                                        <div class="my-1 border-t border-stone-100"></div>
                                        @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE])
                                            <a href="{{ route('admin.home') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">Admin</a>
                                        @endcanany
                                        @can(\App\Permissions::VARIETIES_MANAGE)
                                            <a href="{{ route('varieties.create') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">New variety</a>
                                        @endcan
                                        @can(\App\Permissions::EVENTS_MANAGE)
                                            <a href="{{ route('admin.events.create') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">New event</a>
                                        @endcan
                                    @endcanany
                                    <div class="my-1 border-t border-stone-100"></div>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-stone-50">Profile</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-stone-50">Log out</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endguest
                </nav>

                <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-stone-700 hover:bg-amber-100" aria-label="Open menu" data-testid="mobile-menu-toggle">
                    <svg x-show="!mobileOpen" class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div x-show="mobileOpen" x-cloak class="lg:hidden border-t border-amber-200/60 bg-amber-50/95" data-testid="mobile-menu">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex flex-col gap-1 text-sm text-stone-700">
                    <a href="{{ route('home') }}" class="px-3 py-2 rounded hover:bg-amber-100">Home</a>
                    <a href="{{ route('varieties.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">All varieties</a>
                    <a href="{{ route('listings.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">Marketplace</a>
                    <a href="{{ route('events.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">Training</a>
                    <a href="{{ route('advisories.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">Advisories</a>

                    @guest
                        <div class="border-t border-amber-200/60 my-2"></div>
                        <a href="{{ route('login') }}" class="px-3 py-2 rounded hover:bg-amber-100">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-2 rounded bg-stone-900 text-amber-50 text-center font-medium">Get started</a>
                    @else
                        <div class="border-t border-amber-200/60 my-2"></div>
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 font-medium text-center">Finish onboarding</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded hover:bg-amber-100">Dashboard</a>
                            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE])
                                <a href="{{ route('admin.home') }}" class="px-3 py-2 rounded hover:bg-amber-100">Admin</a>
                            @endcanany
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('varieties.create') }}" class="px-3 py-2 rounded hover:bg-amber-100">New variety</a>
                            @endcan
                            @can(\App\Permissions::EVENTS_MANAGE)
                                <a href="{{ route('admin.events.create') }}" class="px-3 py-2 rounded hover:bg-amber-100">New event</a>
                            @endcan
                            <a href="{{ route('my.listings.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">My listings</a>
                            @can(\App\Permissions::LISTINGS_MANAGE)
                                <a href="{{ route('my.listings.create') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 font-medium text-center">List your harvest</a>
                            @endcan
                            <div class="px-3 py-2 flex items-center gap-2 text-stone-500 text-xs">
                                Signed in as <span class="font-medium text-stone-800">{{ auth()->user()->name }}</span>
                                <x-user-role-badge :user="auth()->user()" />
                            </div>
                            <a href="{{ route('profile.edit') }}" class="px-3 py-2 rounded hover:bg-amber-100">Profile</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-amber-100">Log out</button>
                        </form>
                    @endguest
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-900">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-3 text-sm" data-testid="flash-status">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <main class="flex-1">
            {{ $slot }}
        </main>

        <footer class="bg-stone-900 text-stone-300 mt-16">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 flex flex-col sm:flex-row gap-4 sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 ring-1 ring-orange-700/30"></span>
                    <span class="font-semibold text-stone-100">Mango Orchard</span>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm text-stone-400">
                    <span>A small love letter to the world's mango varieties.</span>
                    <button
                        type="button"
                        onclick="document.cookie = 'cookie_consent=; path=/; max-age=0; SameSite=Lax'; location.reload();"
                        class="text-xs underline text-stone-400 hover:text-stone-100 transition-colors text-left sm:text-center"
                        data-testid="cookie-preferences-reset"
                    >Cookie preferences</button>
                </div>
            </div>
        </footer>
        <x-cookie-banner />
    </body>
</html>
