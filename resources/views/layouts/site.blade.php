<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-form-autofill-meta />

        <title>{{ $title ?? config('app.name') }}</title>

        <link rel="icon" href="{{ app(\App\Settings\Settings::class)->siteLogoUrl() ?? asset('favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=optional" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-theme-bootstrap />
    </head>
    <body class="bg-amber-50 dark:bg-stone-900 text-stone-900 dark:text-stone-100 antialiased min-h-screen flex flex-col">
        <x-readonly-banner />
        <x-dev-banner />
        <x-impersonation-banner />
        <header class="sticky top-0 z-30 backdrop-blur bg-amber-50/80 dark:bg-stone-900/80 border-b border-amber-200/60 dark:border-stone-800" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                    <x-site-logo />
                    <span class="text-stone-900 dark:text-stone-100">{{ config('app.name') }}</span>
                </a>

                <nav class="hidden lg:flex items-center gap-5 text-sm text-stone-700 dark:text-stone-300">
                    <a href="{{ route('home') }}" class="hover:text-orange-700 transition-colors">Home</a>
                    <a href="{{ route('varieties.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('varieties.*') ? 'text-orange-700' : '' }}">All varieties</a>
                    <a href="{{ route('listings.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('listings.*') ? 'text-orange-700' : '' }}">Marketplace</a>
                    <a href="{{ route('events.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('events.*') ? 'text-orange-700' : '' }}">Training</a>
                    <a href="{{ route('advisories.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('advisories.*') ? 'text-orange-700' : '' }}">Advisories</a>
                    <a href="{{ route('gallery.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('gallery.*') ? 'text-orange-700' : '' }}">Gallery</a>
                    <a href="{{ route('mpcp.index') }}" class="hover:text-orange-700 transition-colors {{ request()->routeIs('mpcp.*') ? 'text-orange-700' : '' }}">Mango Directory</a>

                    @guest
                        <a href="{{ route('login') }}" class="hover:text-orange-700 transition-colors">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium shadow-sm">Get started</a>
                        <x-theme-switcher />
                    @else
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium">Finish onboarding</a>
                            <x-theme-switcher />
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-orange-700 transition-colors">Log out</button>
                            </form>
                        @else
                            @can(\App\Permissions::LISTINGS_MANAGE)
                                <a href="{{ route('my.listings.create') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium">List your harvest</a>
                            @endcan
                            <x-theme-switcher />
                            <div class="relative" x-data="{ menu: false }" @click.away="menu = false">
                                <button @click="menu = !menu" type="button"
                                        class="inline-flex items-center justify-center rounded-full hover:ring-2 hover:ring-amber-400 transition-shadow"
                                        aria-label="User menu"
                                        :aria-expanded="menu.toString()"
                                        data-testid="user-menu-trigger">
                                    <x-user-avatar :user="auth()->user()" size="md" />
                                </button>
                                <div x-show="menu" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg overflow-hidden text-stone-700 dark:text-stone-200 py-1">
                                    {{-- Identity header inside the dropdown — keeps the name + role
                                         badge surfaced now that they're no longer in the trigger. --}}
                                    <div class="px-4 py-2.5 border-b border-stone-100 dark:border-stone-700 flex items-center gap-3">
                                        <x-user-avatar :user="auth()->user()" size="md" />
                                        <div class="min-w-0">
                                            <p class="text-[11px] uppercase tracking-wider text-stone-500 dark:text-stone-400">Signed in as</p>
                                            <p class="mt-0.5 text-sm font-medium text-stone-900 dark:text-stone-100 truncate">{{ auth()->user()->name }}</p>
                                            <div class="mt-1">
                                                <x-user-role-badge :user="auth()->user()" />
                                            </div>
                                        </div>
                                    </div>
                                    <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">Dashboard</a>
                                    @can(\App\Permissions::LISTINGS_MANAGE)
                                        <a href="{{ route('my.listings.index') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">My listings</a>
                                    @endcan
                                    @can(\App\Permissions::MONITORING_VIEW)
                                        <a href="{{ route('monitoring.dashboard') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">Pragati Darpan</a>
                                    @endcan
                                    @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::VARIETIES_MANAGE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE, \App\Permissions::MONITORING_MANAGE])
                                        <div class="my-1 border-t border-stone-100 dark:border-stone-700"></div>
                                        @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE, \App\Permissions::MONITORING_MANAGE, \App\Permissions::VARIETIES_MANAGE])
                                            <a href="{{ route('admin.home') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">Admin</a>
                                        @endcanany
                                        @can(\App\Permissions::VARIETIES_MANAGE)
                                            <a href="{{ route('varieties.create') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">New variety</a>
                                        @endcan
                                        @can(\App\Permissions::EVENTS_MANAGE)
                                            <a href="{{ route('admin.events.create') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">New event</a>
                                        @endcan
                                    @endcanany
                                    <div class="my-1 border-t border-stone-100 dark:border-stone-700"></div>
                                    <a href="{{ route('profile.edit') }}" class="block px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">Profile</a>
                                    <form method="POST" action="{{ route('logout') }}">
                                        @csrf
                                        <button type="submit" class="block w-full text-left px-4 py-2 text-sm hover:bg-stone-50 dark:hover:bg-stone-700/50">Log out</button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    @endguest
                </nav>

                <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-stone-700 dark:text-stone-200 hover:bg-amber-100 dark:hover:bg-stone-800" aria-label="Open menu" data-testid="mobile-menu-toggle">
                    <svg x-show="!mobileOpen" class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div x-show="mobileOpen" x-cloak class="lg:hidden border-t border-amber-200/60 dark:border-stone-800 bg-amber-50/95 dark:bg-stone-900/95" data-testid="mobile-menu">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex flex-col gap-1 text-sm text-stone-700 dark:text-stone-200">
                    <a href="{{ route('home') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Home</a>
                    <a href="{{ route('varieties.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">All varieties</a>
                    <a href="{{ route('listings.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Marketplace</a>
                    <a href="{{ route('events.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Training</a>
                    <a href="{{ route('advisories.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Advisories</a>
                    <a href="{{ route('gallery.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Gallery</a>
                    <a href="{{ route('mpcp.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Mango Directory</a>

                    @guest
                        <div class="border-t border-amber-200/60 dark:border-stone-800 my-2"></div>
                        <a href="{{ route('login') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 hover:bg-amber-400 text-center font-medium shadow-sm">Get started</a>
                    @else
                        <div class="border-t border-amber-200/60 dark:border-stone-800 my-2"></div>
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 font-medium text-center">Finish onboarding</a>
                        @else
                            <a href="{{ route('dashboard') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Dashboard</a>
                            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE, \App\Permissions::MONITORING_MANAGE, \App\Permissions::VARIETIES_MANAGE])
                                <a href="{{ route('admin.home') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Admin</a>
                            @endcanany
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('varieties.create') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">New variety</a>
                            @endcan
                            @can(\App\Permissions::EVENTS_MANAGE)
                                <a href="{{ route('admin.events.create') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">New event</a>
                            @endcan
                            @can(\App\Permissions::LISTINGS_MANAGE)
                                <a href="{{ route('my.listings.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">My listings</a>
                                <a href="{{ route('my.listings.create') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 font-medium text-center">List your harvest</a>
                            @endcan
                            @can(\App\Permissions::MONITORING_VIEW)
                                <a href="{{ route('monitoring.dashboard') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Pragati Darpan</a>
                            @endcan
                            <div class="px-3 py-2 flex items-center gap-2 text-stone-500 dark:text-stone-400 text-xs">
                                Signed in as <span class="font-medium text-stone-800 dark:text-stone-200">{{ auth()->user()->name }}</span>
                                <x-user-role-badge :user="auth()->user()" />
                            </div>
                            <a href="{{ route('profile.edit') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Profile</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Log out</button>
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
                    <x-site-logo size="footer" />
                    <div>
                        <p class="text-lg font-semibold text-stone-100">{{ config('app.name') }}</p>
                        <p class="text-xs text-stone-400">an initiative by {{ config('app.owner') }}</p>
                    </div>
                </div>
                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-4 text-sm text-stone-400">
                    <span>A small love letter to the world's mango varieties.</span>
                    <a
                        href="{{ route('cookies.policy') }}"
                        class="text-xs underline text-stone-400 hover:text-stone-100 transition-colors text-left sm:text-center"
                        data-testid="cookie-preferences-reset"
                    >Cookie preferences</a>
                </div>
            </div>
            {{-- NIC credits + ownership disclaimer. Required attribution
                 strip — kept in a separate row so it doesn't dilute the
                 brand line above and reads as official site metadata. --}}
            <div class="border-t border-stone-800">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between text-xs text-stone-400" data-testid="nic-credits">
                    <p>
                        Designed, developed, and maintained by the
                        <span class="font-medium text-stone-200">National Informatics Centre (NIC)</span>.
                    </p>
                    <p class="sm:text-right sm:max-w-xl leading-relaxed">
                        <span class="font-medium text-stone-200">Disclaimer:</span>
                        Content, data, process and operation owned and maintained by the
                        {{ config('app.disclaimer_owner') }}.
                    </p>
                </div>
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pb-4 -mt-2 flex items-center justify-between gap-3 text-[10px] text-stone-500">
                    <span class="inline-flex items-center font-mono" data-testid="app-version-tag">{{ $appVersionTag ?? '' }}</span>
                    <span data-testid="app-copyright">&copy; {{ now()->year }} {{ config('app.owner') }}. All rights reserved.</span>
                </div>
            </div>
        </footer>
        <x-scroll-to-top />
        <x-cookie-banner />
    </body>
</html>
