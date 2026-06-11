<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A field guide to the mango varieties of {{ config('app.district') }} district — origins, seasons, and tasting notes from Himsagar through Ashwina.">
        <x-form-autofill-meta />

        <title>{{ config('app.name') }} — A field guide to mango varieties</title>

        <link rel="icon" type="image/webp" href="/images/LOGO-Square.webp">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=optional" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-theme-bootstrap />
    </head>
    <body class="bg-amber-50 dark:bg-stone-900 text-stone-900 dark:text-stone-100 antialiased">
        <x-readonly-banner />
        <x-dev-banner />
        <x-impersonation-banner />
        <header class="sticky top-0 z-30 backdrop-blur bg-amber-50/80 dark:bg-stone-900/80 border-b border-amber-200/60 dark:border-stone-800" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="#top" class="flex items-center gap-2 font-semibold tracking-tight">
                    <img src="/images/LOGO-Square.webp" alt="{{ config('app.name') }}" class="inline-block w-7 h-7 rounded-full object-cover">
                    <span class="text-stone-900 dark:text-stone-100">{{ config('app.name') }}</span>
                </a>

                <nav class="hidden lg:flex items-center gap-5 text-sm text-stone-700 dark:text-stone-300">
                    <a href="{{ route('varieties.index') }}" class="hover:text-orange-700 transition-colors">All varieties</a>
                    <a href="{{ route('listings.index') }}" class="hover:text-orange-700 transition-colors">Marketplace</a>
                    <a href="{{ route('events.index') }}" class="hover:text-orange-700 transition-colors">Training</a>
                    <a href="{{ route('advisories.index') }}" class="hover:text-orange-700 transition-colors">Advisories</a>
                    <a href="{{ route('gallery.index') }}" class="hover:text-orange-700 transition-colors">Gallery</a>
                    <a href="{{ route('mpcp.index') }}" class="hover:text-orange-700 transition-colors">Mango Directory</a>

                    @guest
                        <a href="{{ route('login') }}" class="hover:text-orange-700 transition-colors">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium shadow-sm">Get started</a>
                        <x-theme-switcher />
                    @else
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium" data-testid="finish-onboarding-link">Finish onboarding</a>
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
                                        class="inline-flex items-center justify-center w-9 h-9 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-700 border border-stone-200 dark:border-stone-700 transition-colors"
                                        aria-label="User menu"
                                        :aria-expanded="menu.toString()"
                                        data-testid="user-menu-trigger">
                                    <svg class="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                        <circle cx="12" cy="8" r="4" stroke-linecap="round" stroke-linejoin="round"/>
                                        <path d="M4 20c0-4 4-7 8-7s8 3 8 7" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                </button>
                                <div x-show="menu" x-cloak x-transition class="absolute right-0 mt-2 w-56 bg-white dark:bg-stone-800 border border-stone-200 dark:border-stone-700 rounded-xl shadow-lg overflow-hidden text-stone-700 dark:text-stone-200 py-1">
                                    {{-- Identity header inside the dropdown — keeps the name + role
                                         badge surfaced now that they're no longer in the trigger. --}}
                                    <div class="px-4 py-2.5 border-b border-stone-100 dark:border-stone-700">
                                        <p class="text-[11px] uppercase tracking-wider text-stone-500 dark:text-stone-400">Signed in as</p>
                                        <p class="mt-0.5 text-sm font-medium text-stone-900 dark:text-stone-100 truncate">{{ auth()->user()->name }}</p>
                                        <div class="mt-1.5">
                                            <x-user-role-badge :user="auth()->user()" />
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

                <button type="button" @click="mobileOpen = !mobileOpen" class="lg:hidden inline-flex items-center justify-center w-10 h-10 rounded-lg text-stone-700 dark:text-stone-300 hover:bg-amber-100 dark:hover:bg-stone-800" aria-label="Open menu" data-testid="mobile-menu-toggle">
                    <svg x-show="!mobileOpen" class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/></svg>
                    <svg x-show="mobileOpen" x-cloak class="w-5 h-5" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 5l10 10M15 5l-10 10" stroke-linecap="round"/></svg>
                </button>
            </div>

            <div x-show="mobileOpen" x-cloak class="lg:hidden border-t border-amber-200/60 dark:border-stone-800 bg-amber-50/95 dark:bg-stone-900/95" data-testid="mobile-menu">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 py-3 flex flex-col gap-1 text-sm text-stone-700 dark:text-stone-300">
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
                            @can(\App\Permissions::LISTINGS_MANAGE)
                                <a href="{{ route('my.listings.index') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">My listings</a>
                                <a href="{{ route('my.listings.create') }}" class="px-3 py-2 rounded bg-amber-500 text-stone-900 font-medium text-center">List your harvest</a>
                            @endcan
                            @can(\App\Permissions::MONITORING_VIEW)
                                <a href="{{ route('monitoring.dashboard') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Pragati Darpan</a>
                            @endcan
                            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE, \App\Permissions::MONITORING_MANAGE, \App\Permissions::VARIETIES_MANAGE])
                                <a href="{{ route('admin.home') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Admin</a>
                            @endcanany
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('varieties.create') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">New variety</a>
                            @endcan
                            <div class="px-3 py-2 flex items-center gap-2 text-stone-500 text-xs">
                                Signed in as <span class="font-medium text-stone-800 dark:text-stone-200">{{ auth()->user()->name }}</span>
                                <x-user-role-badge :user="auth()->user()" />
                            </div>
                            <a href="{{ route('profile.edit') }}" class="px-3 py-2 rounded hover:bg-amber-100 dark:hover:bg-stone-800">Profile</a>
                        @endif
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="w-full text-left px-3 py-2 rounded hover:bg-amber-100">Log out</button>
                        </form>
                    @endguest
                </div>
            </div>
        </header>

        <main id="top">
            @php
                // Two hero styles coexist:
                //   "branding" → official Aamar Malda — Mango Capital block
                //   "guide"    → original orchard-photo / field-guide block
                //
                // The on-page toggle is hidden by default; switch with a URL
                // query param (`?hero=guide` or `?hero=branding`). The cookie
                // is still honoured as a fallback so anyone who set it via the
                // old toggle keeps their preference.
                $heroStyle = request()->query('hero')
                    ?? request()->cookie('hero_style', 'branding');
                if (! in_array($heroStyle, ['branding', 'guide'], true)) {
                    $heroStyle = 'branding';
                }
            @endphp

            @if ($heroStyle === 'branding')
                {{-- ============== Hero: Aamar Malda — The Mango Capital ============== --}}
                <section class="relative overflow-hidden">
                    {{-- Green → orange gradient backdrop. Mirrors the official Malda
                         mango-capital branding and stays legible in both modes. --}}
                    <div aria-hidden="true" class="absolute inset-0 z-0 bg-gradient-to-br from-emerald-900 via-emerald-700 to-orange-500"></div>
                    <div aria-hidden="true" class="absolute inset-0 z-0 pointer-events-none">
                        <div class="absolute -top-24 -left-24 w-96 h-96 rounded-full bg-emerald-300/20 blur-3xl"></div>
                        <div class="absolute -bottom-32 -right-24 w-[32rem] h-[32rem] rounded-full bg-amber-300/20 blur-3xl"></div>
                    </div>

                    {{-- Toggle hidden by default; switch via ?hero=guide URL param.
                         <x-hero-style-toggle current="branding" next="guide" label="Orchard view" tone="light" /> --}}

                    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-20 sm:pb-24 lg:pt-24 lg:pb-28">
                        <div class="grid lg:grid-cols-[auto_1fr] gap-8 lg:gap-12 items-center">
                            <div class="flex justify-center lg:justify-start">
                                <img
                                    src="/images/LOGO-Square.webp"
                                    alt="{{ config('app.name') }} — {{ config('app.tagline') }}, West Bengal, India"
                                    class="w-56 h-56 sm:w-64 sm:h-64 lg:w-72 lg:h-72 rounded-full ring-4 ring-amber-300/40 shadow-2xl"
                                    loading="eager"
                                    decoding="async"
                                />
                            </div>

                            <div class="text-center lg:text-left">
                                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-white leading-[1.05]">
                                    {{ config('app.name') }}
                                </h1>
                                <p class="mt-3 text-xl sm:text-2xl font-medium text-amber-300">
                                    {{ config('app.tagline') }}
                                </p>
                                <p class="mt-6 text-base sm:text-lg text-amber-50/95 max-w-2xl leading-relaxed">
                                    Nestled in the heart of North Bengal, {{ config('app.district') }} is celebrated across India and beyond as the land of the finest mangoes. From the fragrant Himsagar to the giant Fazli, the district's orchards turn every summer into a season of gold — sustaining lakhs of families and carrying the taste of {{ config('app.district') }} to the world.
                                </p>
                                <div class="mt-8 flex flex-wrap gap-3 justify-center lg:justify-start">
                                    <a href="#varieties" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-amber-500 text-stone-900 font-medium hover:bg-amber-400 transition-colors shadow-sm">
                                        Browse varieties
                                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                    <a href="#season" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white/10 text-white font-medium border border-white/30 hover:bg-white/15 transition-colors backdrop-blur">
                                        See season guide
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- ============== Present Status of Malda Mango ============== --}}
                {{-- Renders only with the Aamar Malda branding hero; the field-guide
                     variant deliberately mirrors aamarmalda.wb.gov.in, which goes
                     straight from hero to the "Twelve mangoes worth knowing" grid. --}}
                <section class="bg-white dark:bg-stone-900 border-t border-amber-100 dark:border-stone-800">
                    <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
                        <div class="text-center mb-10">
                            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-emerald-900 dark:text-emerald-200">Present Status of {{ config('app.district') }} Mango</h2>
                            <div aria-hidden="true" class="mt-3 mx-auto w-16 h-0.5 bg-gradient-to-r from-emerald-500 to-amber-500"></div>
                            <p class="mt-4 text-stone-600 dark:text-stone-300">A snapshot of the district's mango economy for the 2025–26 season.</p>
                        </div>

                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-4">
                            @foreach ([
                                ['31,958', 'HECTARES', 'Area under mango cultivation'],
                                ['3,80,776', 'METRIC TONNES', 'Annual production'],
                                ['4,80,000', 'PEOPLE (APPROX.)', 'Directly & indirectly involved'],
                                ['96,602', 'OWNERS', 'Orchard owners in the district'],
                                ['₹1,350', 'PER QUINTAL', 'Average rate of fresh mango'],
                            ] as $stat)
                                <div class="bg-white dark:bg-stone-950 border border-amber-200 dark:border-stone-800 rounded-2xl p-5 text-center hover:border-amber-400 dark:hover:border-amber-700 transition-colors">
                                    <div class="text-2xl sm:text-3xl font-semibold text-amber-700 dark:text-amber-300 tracking-tight">{{ $stat[0] }}</div>
                                    <div class="mt-1 text-[11px] uppercase tracking-wide text-stone-600 dark:text-stone-300 font-medium">{{ $stat[1] }}</div>
                                    <div class="mt-3 text-xs text-stone-500 dark:text-stone-400">{{ $stat[2] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @else
                {{-- ============== Hero: A field guide to mango varieties ============== --}}
                @php
                    // Drop a JPG/PNG/WebP at public/images/hero-orchard-photo.<ext> to use a real photo.
                    // WebP is preferred (smallest), then JPG, then PNG.
                    $heroPhotoCandidates = ['hero-orchard-photo.webp', 'hero-orchard-photo.jpg', 'hero-orchard-photo.png'];
                    $heroPhoto = null;
                    foreach ($heroPhotoCandidates as $candidate) {
                        if (file_exists(public_path("images/{$candidate}"))) {
                            $heroPhoto = asset("images/{$candidate}");
                            break;
                        }
                    }
                @endphp

                <section class="relative overflow-hidden">
                    @if ($heroPhoto)
                        {{-- Full-bleed photo backdrop. Cream gradient overlays on the left, top
                             and bottom blend the image seamlessly into the page; text overlays
                             the left half on top. --}}
                        <div aria-hidden="true" class="absolute inset-0 z-0">
                            <img
                                src="{{ $heroPhoto }}"
                                alt=""
                                class="w-full h-full object-cover lg:translate-x-[12%] origin-right"
                                loading="eager"
                                decoding="async"
                            />
                            {{-- Left → right cream wash. Solid on the left so the headline reads,
                                 fades out by the centre so the photo dominates the right half.
                                 Covers the slim left-edge gap created by the photo's translate.
                                 Dark-mode variants swap the cream wash for stone-900 so the
                                 hero region blends with the dark body bg instead of glowing. --}}
                            <div class="absolute inset-0 bg-amber-50/80 dark:bg-stone-900/80 lg:hidden"></div>
                            <div class="hidden lg:block absolute inset-0 bg-gradient-to-r from-amber-50 dark:from-stone-900 from-35% via-amber-50/85 dark:via-stone-900/85 via-55% to-transparent to-80%"></div>
                            {{-- Top + bottom fades so the photo dissolves into the page chrome. --}}
                            <div class="absolute inset-x-0 top-0 h-20 bg-gradient-to-b from-amber-50 dark:from-stone-900 to-transparent"></div>
                            <div class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-amber-50 dark:from-stone-900 via-amber-50/80 dark:via-stone-900/80 to-transparent"></div>
                        </div>
                    @else
                        {{-- Decorative gradient blobs while no hero photo is present.
                             Dim-tones in dark mode so the blobs read as subtle ambient
                             hue rather than glowing pastel highlights against the dark bg. --}}
                        <div aria-hidden="true" class="absolute inset-0 -z-10">
                            <div class="absolute -top-32 -left-24 w-96 h-96 rounded-full bg-amber-300/40 dark:bg-amber-800/30 blur-3xl"></div>
                            <div class="absolute top-20 -right-24 w-[28rem] h-[28rem] rounded-full bg-rose-300/30 dark:bg-rose-800/20 blur-3xl"></div>
                            <div class="absolute bottom-0 left-1/3 w-80 h-80 rounded-full bg-lime-300/30 dark:bg-lime-800/20 blur-3xl"></div>
                        </div>
                    @endif

                    {{-- Toggle hidden by default; switch via ?hero=branding URL param.
                         <x-hero-style-toggle current="guide" next="branding" label="Aamar Malda view" tone="dark" /> --}}

                    <div class="relative z-10 mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28 lg:pt-32 lg:pb-36">
                        <div class="@if ($heroPhoto) lg:w-1/2 @else grid lg:grid-cols-2 gap-12 items-center @endif">
                            <div>
                                <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 dark:bg-amber-950 text-amber-900 dark:text-amber-200 text-xs font-medium tracking-wide uppercase">
                                    <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                    Mango Orchard
                                </span>
                                <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-stone-900 dark:text-stone-100 leading-[1.05]">
                                    The world tastes <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500">sweeter</span> in mango season.
                                </h1>
                                <p class="mt-6 text-lg text-stone-800 dark:text-stone-200 max-w-xl leading-relaxed">
                                    In the sunlit {{ config('app.district') }} orchard, a rhythmic dance of nature and labor unfolds as workers methodically harvest heavy, golden mangoes for the upcoming market rush. These fragrant fruits carry the distinct geography of their grove in every bite, inviting you to explore the rich varieties that define the season.
                                </p>
                                <div class="mt-8 flex flex-wrap gap-3">
                                    <a href="#varieties" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors">
                                        Browse varieties
                                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                    </a>
                                    <a href="#season" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white dark:bg-stone-800 text-stone-900 dark:text-stone-100 font-medium border border-stone-200 dark:border-stone-700 hover:border-stone-400 dark:hover:border-stone-500 transition-colors">
                                        See season guide
                                    </a>
                                </div>
                            </div>

                            @unless ($heroPhoto)
                                <div class="relative hidden lg:block">
                                    <img
                                        src="{{ asset('images/hero-orchard-canopy.svg') }}"
                                        alt="A mango tree branch heavy with ripe yellow and red mangoes against a warm sky"
                                        class="w-full h-auto max-h-[28rem] object-contain drop-shadow-2xl"
                                        loading="eager"
                                        decoding="async"
                                    />
                                </div>
                            @endunless
                        </div>

                        <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8 border-t border-amber-200/70 dark:border-stone-800 pt-10">
                            @foreach ([
                                ['31,958', 'Mango cultivation area in hectares'],
                                ['3,80,776', 'Annual production in metric tonnes'],
                                ['96,602', 'Orchard owners in the district'],
                                [$varieties->count(), 'mango varieties'],
                            ] as $stat)
                                <div>
                                    <div class="text-3xl sm:text-4xl font-semibold text-stone-900 dark:text-stone-100 tracking-tight">{{ $stat[0] }}</div>
                                    <div class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $stat[1] }}</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </section>
            @endif

            {{-- ============== MPCP summary card ============== --}}
            {{-- Common to both hero variants — the field-guide variant only
                 mirrors the prod HERO byte-for-byte; the rest of the welcome
                 page is free to carry our additions. --}}
            <x-mpcp-summary />
            <x-gallery-summary />

            <section id="varieties" class="bg-white dark:bg-stone-900 border-t border-amber-100 dark:border-stone-800">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-12">
                        <div>
                            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ Str::ucfirst(\Illuminate\Support\Number::spell($varieties->count())) }} mangoes worth knowing</h2>
                            <p class="mt-3 text-stone-600 dark:text-stone-300 max-w-2xl">Each cultivar has its own shape, color, ripening cue and flavor signature. Hover a card for the full tasting note.</p>
                        </div>
                        <a href="{{ route('varieties.index') }}" class="text-sm text-orange-700 hover:text-orange-900 font-medium">See full list →</a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($varieties as $variety)
                            <article class="group relative overflow-hidden rounded-2xl bg-white dark:bg-stone-950 border border-stone-200/80 dark:border-stone-800 hover:border-stone-300 dark:hover:border-stone-600 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                <a href="{{ route('varieties.show', $variety) }}" class="block">
                                    <div class="relative h-44 overflow-hidden bg-gradient-to-br {{ $variety->gradient_classes }}">
                                        @if ($variety->image_url)
                                            <img src="{{ $variety->image_url }}" alt="{{ $variety->name }}"
                                                 class="absolute inset-0 w-full h-full object-cover group-hover:scale-105 transition-transform duration-500"
                                                 loading="lazy" decoding="async">
                                        @else
                                            <div aria-hidden="true" class="absolute -bottom-10 -right-6 w-44 h-52 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                                            <div aria-hidden="true" class="absolute -top-8 -left-6 w-32 h-32 rounded-full bg-white/20 blur-xl"></div>
                                        @endif
                                        <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $variety->accent_classes }} backdrop-blur">
                                            {{ $variety->season }}
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <h3 class="text-xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $variety->name }}</h3>
                                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $variety->origin }}</p>
                                        <p class="mt-4 text-sm text-stone-700 dark:text-stone-300 leading-relaxed">{{ $variety->flavor }}</p>
                                        @if (! empty($variety->tags))
                                            <div class="mt-5 flex flex-wrap gap-2">
                                                @foreach ($variety->tags as $tag)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700">{{ $tag }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </a>
                            </article>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="season" class="bg-gradient-to-b from-amber-50 to-orange-50 dark:from-stone-900 dark:to-stone-950 border-t border-amber-100 dark:border-stone-800">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">When each variety peaks</h2>
                        <p class="mt-3 text-stone-600 dark:text-stone-300">A quick visual of when to look for each mango in its prime. Times shift a little year to year with the rains.</p>
                    </div>

                    @php
                        $months = ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];
                    @endphp

                    <div class="mt-10 rounded-2xl border border-stone-200/80 dark:border-stone-800 bg-white/70 dark:bg-stone-900/70 backdrop-blur overflow-hidden">
                        <table class="w-full text-sm table-fixed">
                            <thead>
                                <tr class="text-stone-500 dark:text-stone-400">
                                    <th class="text-left font-medium p-2 sm:p-4 w-24 sm:w-40">Variety</th>
                                    @foreach ($months as $m)
                                        <th class="font-medium px-0.5 sm:px-1.5 py-2 sm:py-4 text-center text-[11px] sm:text-sm">{{ $m }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($varieties as $variety)
                                    <tr class="hover:bg-amber-50/60 dark:hover:bg-stone-800 transition-colors">
                                        <td class="p-2 sm:p-4 font-medium text-stone-800 dark:text-stone-200 text-xs sm:text-sm break-words">{{ $variety->name }}</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td class="px-0.5 sm:px-1 py-2 text-center align-middle">
                                                @if ($i >= $variety->season_start && $i <= $variety->season_end)
                                                    <span class="inline-block w-full h-2 rounded-full bg-gradient-to-r from-amber-400 to-orange-500"></span>
                                                @else
                                                    <span class="inline-block w-full h-2 rounded-full bg-stone-100 dark:bg-stone-800"></span>
                                                @endif
                                            </td>
                                        @endfor
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>

            <section id="about" class="bg-white dark:bg-stone-900 border-t border-amber-100 dark:border-stone-800">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="grid lg:grid-cols-3 gap-10">
                        <div class="lg:col-span-1">
                            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">How to pick a ripe one</h2>
                            <p class="mt-4 text-stone-600 dark:text-stone-300">Color is the worst guide — a Bombai or Langra stays green even when perfectly ripe. Trust your other senses.</p>
                        </div>
                        <div class="lg:col-span-2 grid sm:grid-cols-2 gap-5">
                            @foreach ([
                                ['Squeeze gently', 'A ripe mango yields slightly to pressure, the way a ripe avocado or peach does. Hard means a few more days on the counter.'],
                                ['Smell the stem end', 'Bring it to your nose. The strongest mangoes — Himsagar, Lakshmanbhog, Gopalbhog — announce themselves before you taste them.'],
                                ['Look at the shoulders', 'The flesh around the stem should be plump and slightly rounded outward, not sunken. Sunken means it was picked too early.'],
                                ['Skip the fridge', 'Ripen at room temperature. Refrigerate only after fully ripe, and eat within two or three days for best flavor.'],
                            ] as $tip)
                                <div class="rounded-xl border border-stone-200 dark:border-stone-700 p-6 hover:border-orange-300 dark:hover:border-orange-700 transition-colors">
                                    <h3 class="font-semibold text-stone-900 dark:text-stone-100">{{ $tip[0] }}</h3>
                                    <p class="mt-2 text-sm text-stone-600 dark:text-stone-300 leading-relaxed">{{ $tip[1] }}</p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer class="bg-stone-900 text-stone-300">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 flex flex-col sm:flex-row gap-6 sm:items-center sm:justify-between">
                <div class="flex items-center gap-3">
                    <img src="/images/LOGO-Square.webp" alt="{{ config('app.name') }}" class="inline-block w-40 h-40 sm:w-48 sm:h-48 rounded-full object-cover">
                    <div>
                        <p class="text-lg font-semibold text-stone-100">{{ config('app.name') }}</p>
                        <p class="text-xs text-stone-400">an initiative by {{ config('app.owner') }}</p>
                    </div>
                </div>
                <p class="text-sm text-stone-400">A small love letter to the world's mango varieties.</p>
                <a
                    href="{{ route('cookies.policy') }}"
                    class="text-xs underline text-stone-400 hover:text-stone-100 transition-colors"
                    data-testid="cookie-preferences-reset"
                >Cookie preferences</a>
            </div>
            {{-- NIC credits + ownership disclaimer. Mirrored verbatim from
                 the site layout footer — keep both in lockstep when the
                 attribution copy changes. --}}
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
