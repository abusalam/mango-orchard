<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="A field guide to the world's most beloved mango varieties — origins, seasons, and flavor notes from Alphonso to Nam Dok Mai.">

        <title>{{ config('app.name', 'Mango Orchard') }} — A field guide to mango varieties</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-amber-50 text-stone-900 antialiased">
        <header class="sticky top-0 z-30 backdrop-blur bg-amber-50/80 border-b border-amber-200/60" x-data="{ mobileOpen: false }">
            <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="#top" class="flex items-center gap-2 font-semibold tracking-tight">
                    <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 shadow-inner ring-1 ring-orange-700/20"></span>
                    <span class="text-stone-900">Mango Orchard</span>
                </a>

                <nav class="hidden lg:flex items-center gap-5 text-sm text-stone-700">
                    <a href="#varieties" class="hover:text-orange-700 transition-colors">Varieties</a>
                    <a href="#season" class="hover:text-orange-700 transition-colors">Season Guide</a>
                    <a href="#about" class="hover:text-orange-700 transition-colors">About</a>
                    <a href="{{ route('varieties.index') }}" class="hover:text-orange-700 transition-colors">All varieties</a>

                    @guest
                        <a href="{{ route('login') }}" class="hover:text-orange-700 transition-colors">Log in</a>
                        <a href="{{ route('register') }}" class="px-3 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 transition-colors text-xs">Get started</a>
                    @else
                        @if (! auth()->user()->hasCompletedOnboarding())
                            <a href="{{ route('onboarding.start') }}" class="px-3 py-1 rounded-full bg-amber-500 text-stone-900 hover:bg-amber-400 transition-colors text-xs font-medium" data-testid="finish-onboarding-link">Finish onboarding</a>
                            <form method="POST" action="{{ route('logout') }}" class="inline">
                                @csrf
                                <button type="submit" class="hover:text-orange-700 transition-colors">Log out</button>
                            </form>
                        @else
                            <a href="{{ route('dashboard') }}" class="hover:text-orange-700 transition-colors">Dashboard</a>
                            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE])
                                <a href="{{ route('admin.home') }}" class="hover:text-orange-700 transition-colors">Admin</a>
                            @endcanany
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('varieties.create') }}" class="px-3 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 transition-colors text-xs">New variety</a>
                            @endcan
                            <div class="relative" x-data="{ menu: false }" @click.away="menu = false">
                                <button @click="menu = !menu" type="button" class="flex items-center gap-2 hover:text-orange-700 transition-colors">
                                    <span>{{ auth()->user()->name }}</span>
                                    <x-user-role-badge :user="auth()->user()" />
                                    <svg class="w-3 h-3" viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 5l3 3 3-3" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </button>
                                <div x-show="menu" x-cloak x-transition class="absolute right-0 mt-2 w-44 bg-white border border-stone-200 rounded-xl shadow-lg overflow-hidden text-stone-700">
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
                    <a href="#varieties" @click="mobileOpen = false" class="px-3 py-2 rounded hover:bg-amber-100">Varieties</a>
                    <a href="#season" @click="mobileOpen = false" class="px-3 py-2 rounded hover:bg-amber-100">Season Guide</a>
                    <a href="#about" @click="mobileOpen = false" class="px-3 py-2 rounded hover:bg-amber-100">About</a>
                    <a href="{{ route('varieties.index') }}" class="px-3 py-2 rounded hover:bg-amber-100">All varieties</a>

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
                            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE])
                                <a href="{{ route('admin.home') }}" class="px-3 py-2 rounded hover:bg-amber-100">Admin</a>
                            @endcanany
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('varieties.create') }}" class="px-3 py-2 rounded hover:bg-amber-100">New variety</a>
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

        <main id="top">
            <section class="relative overflow-hidden">
                <div aria-hidden="true" class="absolute inset-0 -z-10">
                    <div class="absolute -top-32 -left-24 w-96 h-96 rounded-full bg-amber-300/40 blur-3xl"></div>
                    <div class="absolute top-20 -right-24 w-[28rem] h-[28rem] rounded-full bg-rose-300/30 blur-3xl"></div>
                    <div class="absolute bottom-0 left-1/3 w-80 h-80 rounded-full bg-lime-300/30 blur-3xl"></div>
                </div>

                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 pt-16 pb-20 sm:pt-24 sm:pb-28 lg:pt-32 lg:pb-36">
                    <div class="grid lg:grid-cols-2 gap-12 items-center">
                        <div>
                            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-medium tracking-wide uppercase">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                                A field guide
                            </span>
                            <h1 class="mt-5 text-4xl sm:text-5xl lg:text-6xl font-semibold tracking-tight text-stone-900 leading-[1.05]">
                                The world tastes <span class="text-transparent bg-clip-text bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500">sweeter</span> in mango season.
                            </h1>
                            <p class="mt-6 text-lg text-stone-700 max-w-xl leading-relaxed">
                                From the saffron-rich Alphonso of the Konkan coast to the honey-soft Ataulfo of Chiapas, mangoes carry the geography of their orchard in every bite. Explore the varieties that define a season.
                            </p>
                            <div class="mt-8 flex flex-wrap gap-3">
                                <a href="#varieties" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors">
                                    Browse varieties
                                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                                </a>
                                <a href="#season" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-white text-stone-900 font-medium border border-stone-200 hover:border-stone-400 transition-colors">
                                    See season guide
                                </a>
                            </div>
                        </div>

                        <div class="relative hidden lg:block">
                            <div class="relative h-[28rem] w-full">
                                <div class="absolute top-4 left-8 w-56 h-72 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-600 shadow-2xl rotate-[-12deg]"></div>
                                <div class="absolute top-24 right-6 w-48 h-60 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-gradient-to-br from-lime-300 via-amber-400 to-orange-600 shadow-xl rotate-[18deg]"></div>
                                <div class="absolute bottom-2 left-24 w-44 h-56 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-gradient-to-br from-amber-200 via-orange-300 to-rose-400 shadow-xl rotate-[6deg]"></div>
                                <div class="absolute top-2 right-24 w-10 h-32 rounded-full bg-gradient-to-b from-green-700 to-green-900 rotate-12 origin-top"></div>
                            </div>
                        </div>
                    </div>

                    <div class="mt-16 grid grid-cols-2 sm:grid-cols-4 gap-6 sm:gap-8 border-t border-amber-200/70 pt-10">
                        @foreach ([
                            ['1,000+', 'cultivars worldwide'],
                            ['~4000', 'years cultivated'],
                            ['100+', 'growing countries'],
                            [$varieties->count(), 'varieties featured'],
                        ] as $stat)
                            <div>
                                <div class="text-3xl sm:text-4xl font-semibold text-stone-900 tracking-tight">{{ $stat[0] }}</div>
                                <div class="mt-1 text-sm text-stone-600">{{ $stat[1] }}</div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            <section id="varieties" class="bg-white border-t border-amber-100">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-12">
                        <div>
                            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900">{{ Str::ucfirst(\Illuminate\Support\Number::spell($varieties->count())) }} mangoes worth knowing</h2>
                            <p class="mt-3 text-stone-600 max-w-2xl">Each cultivar has its own shape, color, ripening cue and flavor signature. Hover a card for the full tasting note.</p>
                        </div>
                        <a href="{{ route('varieties.index') }}" class="text-sm text-orange-700 hover:text-orange-900 font-medium">See full list →</a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($varieties as $variety)
                            <article class="group relative overflow-hidden rounded-2xl bg-stone-50 border border-stone-200/80 hover:border-stone-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                                <a href="{{ route('varieties.show', $variety) }}" class="block">
                                    <div class="relative h-44 overflow-hidden bg-gradient-to-br {{ $variety->gradient_classes }}">
                                        <div aria-hidden="true" class="absolute -bottom-10 -right-6 w-44 h-52 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                                        <div aria-hidden="true" class="absolute -top-8 -left-6 w-32 h-32 rounded-full bg-white/20 blur-xl"></div>
                                        <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $variety->accent_classes }}">
                                            {{ $variety->season }}
                                        </div>
                                    </div>
                                    <div class="p-6">
                                        <h3 class="text-xl font-semibold tracking-tight text-stone-900">{{ $variety->name }}</h3>
                                        <p class="mt-1 text-sm text-stone-500">{{ $variety->origin }}</p>
                                        <p class="mt-4 text-sm text-stone-700 leading-relaxed">{{ $variety->flavor }}</p>
                                        @if (! empty($variety->tags))
                                            <div class="mt-5 flex flex-wrap gap-2">
                                                @foreach ($variety->tags as $tag)
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">{{ $tag }}</span>
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

            <section id="season" class="bg-gradient-to-b from-amber-50 to-orange-50 border-t border-amber-100">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="max-w-2xl">
                        <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900">When each variety peaks</h2>
                        <p class="mt-3 text-stone-600">A quick visual of when to look for each mango in its prime. Times shift a little year to year with the rains.</p>
                    </div>

                    @php
                        $months = ['J', 'F', 'M', 'A', 'M', 'J', 'J', 'A', 'S', 'O', 'N', 'D'];
                    @endphp

                    <div class="mt-10 overflow-x-auto rounded-2xl border border-stone-200/80 bg-white/70 backdrop-blur">
                        <table class="w-full min-w-[640px] text-sm">
                            <thead>
                                <tr class="text-stone-500">
                                    <th class="text-left font-medium p-4 w-40">Variety</th>
                                    @foreach ($months as $m)
                                        <th class="font-medium px-1.5 py-4 text-center">{{ $m }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-stone-100">
                                @foreach ($varieties as $variety)
                                    <tr class="hover:bg-amber-50/60 transition-colors">
                                        <td class="p-4 font-medium text-stone-800">{{ $variety->name }}</td>
                                        @for ($i = 1; $i <= 12; $i++)
                                            <td class="px-1 py-2 text-center align-middle">
                                                @if ($i >= $variety->season_start && $i <= $variety->season_end)
                                                    <span class="inline-block w-full h-2 rounded-full bg-gradient-to-r from-amber-400 to-orange-500"></span>
                                                @else
                                                    <span class="inline-block w-full h-2 rounded-full bg-stone-100"></span>
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

            <section id="about" class="bg-white border-t border-amber-100">
                <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-20 sm:py-24">
                    <div class="grid lg:grid-cols-3 gap-10">
                        <div class="lg:col-span-1">
                            <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-stone-900">How to pick a ripe one</h2>
                            <p class="mt-4 text-stone-600">Color is the worst guide — a Keitt or Langra stays green even when perfectly ripe. Trust your other senses.</p>
                        </div>
                        <div class="lg:col-span-2 grid sm:grid-cols-2 gap-5">
                            @foreach ([
                                ['Squeeze gently', 'A ripe mango yields slightly to pressure, the way a ripe avocado or peach does. Hard means a few more days on the counter.'],
                                ['Smell the stem end', 'Bring it to your nose. The strongest mangoes — Alphonso, Chaunsa, Carabao — announce themselves before you taste them.'],
                                ['Look at the shoulders', 'The flesh around the stem should be plump and slightly rounded outward, not sunken. Sunken means it was picked too early.'],
                                ['Skip the fridge', 'Ripen at room temperature. Refrigerate only after fully ripe, and eat within two or three days for best flavor.'],
                            ] as $tip)
                                <div class="rounded-xl border border-stone-200 p-6 hover:border-orange-300 transition-colors">
                                    <h3 class="font-semibold text-stone-900">{{ $tip[0] }}</h3>
                                    <p class="mt-2 text-sm text-stone-600 leading-relaxed">{{ $tip[1] }}</p>
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
                    <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 ring-1 ring-orange-700/30"></span>
                    <span class="font-semibold text-stone-100">Mango Orchard</span>
                </div>
                <p class="text-sm text-stone-400">A small love letter to the world's mango varieties.</p>
                <p class="text-sm text-stone-500">v{{ app()->version() }}</p>
            </div>
        </footer>
    </body>
</html>
