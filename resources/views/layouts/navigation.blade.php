<nav x-data="{ open: false }" class="bg-amber-50/80 backdrop-blur border-b border-amber-200/60 sticky top-0 z-30">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                        <span class="inline-block w-7 h-7 rounded-full bg-gradient-to-br from-yellow-300 via-orange-400 to-rose-500 shadow-inner ring-1 ring-orange-700/20"></span>
                        <span class="text-stone-900">Mango Orchard</span>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        {{ __('Dashboard') }}
                    </x-nav-link>
                    <x-nav-link :href="route('home')" :active="request()->routeIs('home')">
                        {{ __('Home') }}
                    </x-nav-link>
                    <x-nav-link :href="route('varieties.index')" :active="request()->routeIs('varieties.*')">
                        {{ __('Varieties') }}
                    </x-nav-link>
                    @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE])
                        <x-nav-link :href="route('admin.home')" :active="request()->routeIs('admin.*')">
                            {{ __('Admin') }}
                        </x-nav-link>
                    @endcanany
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-stone-600 hover:text-stone-900 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>
                            <x-user-role-badge :user="Auth::user()" />

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                    onclick="event.preventDefault();
                                                this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-stone-600 hover:text-stone-900 hover:bg-amber-100 focus:outline-none focus:bg-amber-100 focus:text-stone-900 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden border-t border-amber-200/60 bg-amber-50/95">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                {{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('home')" :active="request()->routeIs('home')">
                {{ __('Home') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('varieties.index')" :active="request()->routeIs('varieties.*')">
                {{ __('Varieties') }}
            </x-responsive-nav-link>
            @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::ROLES_MANAGE, \App\Permissions::SETTINGS_MANAGE, \App\Permissions::TELEMETRY_VIEW, \App\Permissions::USERS_IMPERSONATE, \App\Permissions::EVENTS_MANAGE, \App\Permissions::ADVISORIES_MANAGE])
                <x-responsive-nav-link :href="route('admin.home')" :active="request()->routeIs('admin.*')">
                    {{ __('Admin') }}
                </x-responsive-nav-link>
            @endcanany
        </div>

        <div class="pt-4 pb-1 border-t border-amber-200/60">
            <div class="px-4">
                <div class="flex items-center gap-2">
                    <span class="font-medium text-base text-stone-900">{{ Auth::user()->name }}</span>
                    <x-user-role-badge :user="Auth::user()" />
                </div>
                <div class="font-medium text-sm text-stone-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault();
                                        this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
