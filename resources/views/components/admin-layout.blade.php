@props(['title' => 'Admin', 'active' => 'users'])

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <x-form-autofill-meta />

        <title>{{ $title }} — {{ config('app.name') }} Admin</title>

        <link rel="icon" href="{{ app(\App\Settings\Settings::class)->siteLogoUrl() ?? asset('favicon.svg') }}">
        <link rel="preconnect" href="https://fonts.bunny.net" crossorigin>
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=optional" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
        <x-theme-bootstrap />
    </head>
    <body class="bg-stone-50 dark:bg-stone-900 text-stone-900 dark:text-stone-100 antialiased min-h-screen flex flex-col">
        <x-readonly-banner />
        <x-dev-banner />
        <x-impersonation-banner />
        <header class="border-b border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
                <a href="{{ route('home') }}" class="flex items-center gap-2 font-semibold tracking-tight">
                    <x-site-logo />
                    <span class="text-stone-900 dark:text-stone-100">{{ config('app.name') }} Admin</span>
                </a>
                <div class="flex items-center gap-4 text-sm">
                    <x-theme-switcher />
                    <a href="{{ route('home') }}" class="text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100">Back to site</a>
                    <form method="POST" action="{{ route('logout') }}" class="inline">
                        @csrf
                        <button type="submit" class="text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100">Log out</button>
                    </form>
                </div>
            </div>
        </header>

        @if (session('status'))
            <div class="bg-emerald-50 border-b border-emerald-200 text-emerald-900">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-3 text-sm" data-testid="flash-status">
                    {{ session('status') }}
                </div>
            </div>
        @endif

        <div class="flex-1 mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8 py-8 grid grid-cols-1 lg:grid-cols-[220px_1fr] gap-4 lg:gap-8">
            <aside x-data="{ menuOpen: window.matchMedia('(min-width: 1024px)').matches }"
                   class="lg:sticky lg:top-4 lg:self-start">

                {{-- Mobile-only disclosure toggle. On lg+ the nav is always
                     visible (lg:!block below) and this button is hidden. --}}
                <button type="button"
                        @click="menuOpen = !menuOpen"
                        :aria-expanded="menuOpen.toString()"
                        aria-controls="admin-sidebar-nav"
                        class="lg:hidden w-full flex items-center justify-between gap-2 bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl px-4 py-3 text-sm font-medium text-stone-800 dark:text-stone-200"
                        data-testid="admin-menu-toggle">
                    <span class="inline-flex items-center gap-2">
                        <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path d="M3 5h14M3 10h14M3 15h14" stroke-linecap="round"/>
                        </svg>
                        Admin menu
                    </span>
                    <svg class="w-4 h-4 transition-transform" :class="menuOpen ? 'rotate-180' : ''" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path d="M5 8l5 5 5-5" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </button>

                {{-- x-show drives the mobile collapse; lg:!block overrides the
                     inline display:none so desktop always shows the nav. --}}
                <nav id="admin-sidebar-nav"
                     x-show="menuOpen"
                     class="mt-2 lg:mt-0 lg:!block bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-2 text-sm lg:max-h-[calc(100vh-2rem)] lg:overflow-y-auto">
                    @can(\App\Permissions::USERS_MANAGE)
                        <a href="{{ route('admin.users.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'users',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'users',
                           ])>Users</a>
                        <a href="{{ route('admin.role-applications.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'role-applications',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'role-applications',
                           ])>Role applications</a>
                        <a href="{{ route('admin.role-delegations.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'role-delegations',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'role-delegations',
                           ])>Role delegations</a>
                    @endcan
                    @can(\App\Permissions::USERS_IMPERSONATE)
                        <a href="{{ route('admin.impersonate.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'impersonate',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'impersonate',
                           ])>Impersonation</a>
                    @endcan
                    @can(\App\Permissions::ROLES_MANAGE)
                        <a href="{{ route('admin.roles.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'roles',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'roles',
                           ])>Roles &amp; permissions</a>
                    @endcan
                    @can(\App\Permissions::SETTINGS_MANAGE)
                        <a href="{{ route('admin.settings.edit') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'settings',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'settings',
                           ])>Settings</a>
                    @endcan
                    {{-- Email templates is reachable for any module
                         admin (Niyantrak / Curator) as well as sysadmins;
                         the controller's canTouch() filter scopes the
                         view per holder so this link never lands on a
                         403. --}}
                    @canany([\App\Permissions::SETTINGS_MANAGE, \App\Permissions::MONITORING_MANAGE, \App\Permissions::VARIETIES_MANAGE])
                        <a href="{{ route('admin.email-templates.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'email-templates',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'email-templates',
                           ])>Email templates</a>
                    @endcanany
                    @can(\App\Permissions::SETTINGS_MANAGE)
                        <a href="{{ route('admin.system.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'system',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'system',
                           ])>System</a>
                    @endcan
                    @can(\App\Permissions::TELEMETRY_VIEW)
                        <a href="{{ route('admin.telemetry.index') }}"
                           @class([
                               'block px-3 py-2 rounded-lg font-medium',
                               'bg-stone-900 text-amber-50' => $active === 'telemetry',
                               'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'telemetry',
                           ])>Activity</a>
                    @endcan
                    @canany([\App\Permissions::USERS_MANAGE, \App\Permissions::VARIETIES_MANAGE])
                        <div class="mt-2 pt-2 border-t border-stone-100 dark:border-stone-800">
                            <p class="px-3 py-1 text-xs uppercase tracking-wider text-stone-500 dark:text-stone-400">Mango Orchard</p>
                            @can(\App\Permissions::USERS_MANAGE)
                                <a href="{{ route('admin.mango-orchard.access.index') }}"
                                   @class([
                                       'block px-3 py-2 rounded-lg font-medium',
                                       'bg-stone-900 text-amber-50' => $active === 'mango-access',
                                       'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'mango-access',
                                   ])>Module access</a>
                            @endcan
                            @can(\App\Permissions::VARIETIES_MANAGE)
                                <a href="{{ route('admin.mango-orchard.newsletter.index') }}"
                                   @class([
                                       'block px-3 py-2 rounded-lg font-medium',
                                       'bg-stone-900 text-amber-50' => $active === 'mango-newsletter',
                                       'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'mango-newsletter',
                                   ])>Newsletter</a>
                            @endcan
                        </div>
                    @endcanany
                    @can(\App\Permissions::MONITORING_MANAGE)
                        <div class="mt-2 pt-2 border-t border-stone-100 dark:border-stone-800">
                            <p class="px-3 py-1 text-xs uppercase tracking-wider text-stone-500 dark:text-stone-400">Pragati Darpan</p>
                            <a href="{{ route('admin.monitoring.access.index') }}"
                               @class([
                                   'block px-3 py-2 rounded-lg font-medium',
                                   'bg-stone-900 text-amber-50' => $active === 'monitoring-access',
                                   'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'monitoring-access',
                               ])>Module access</a>
                            <a href="{{ route('admin.monitoring.hierarchy.index') }}"
                               @class([
                                   'block px-3 py-2 rounded-lg font-medium',
                                   'bg-stone-900 text-amber-50' => $active === 'monitoring-hierarchy',
                                   'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'monitoring-hierarchy',
                               ])>Hierarchy</a>
                            <a href="{{ route('admin.monitoring.designations.index') }}"
                               @class([
                                   'block px-3 py-2 rounded-lg font-medium',
                                   'bg-stone-900 text-amber-50' => $active === 'monitoring-designations',
                                   'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'monitoring-designations',
                               ])>Designations</a>
                        </div>
                    @endcan
                    @can(\App\Permissions::MPCP_MANAGE)
                        <div class="mt-2 pt-2 border-t border-stone-100 dark:border-stone-800">
                            <p class="px-3 py-1 text-xs uppercase tracking-wider text-stone-500 dark:text-stone-400">Mango Promotion</p>
                            <a href="{{ route('admin.mpcp.index') }}"
                               @class([
                                   'block px-3 py-2 rounded-lg font-medium',
                                   'bg-stone-900 text-amber-50' => $active === 'mpcp',
                                   'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'mpcp',
                               ])>MPCP directory</a>
                        </div>
                    @endcan
                    @can(\App\Permissions::GALLERY_MANAGE)
                        <div class="mt-2 pt-2 border-t border-stone-100 dark:border-stone-800">
                            <p class="px-3 py-1 text-xs uppercase tracking-wider text-stone-500 dark:text-stone-400">Mango Gallery</p>
                            <a href="{{ route('admin.gallery.index') }}"
                               @class([
                                   'block px-3 py-2 rounded-lg font-medium',
                                   'bg-stone-900 text-amber-50' => $active === 'gallery',
                                   'text-stone-700 dark:text-stone-300 hover:bg-stone-100 dark:hover:bg-stone-800' => $active !== 'gallery',
                               ])>Albums</a>
                        </div>
                    @endcan
                </nav>
            </aside>

            <main>
                {{ $slot }}
            </main>
        </div>
        {{-- NIC credits + ownership disclaimer. Compact single-strip
             admin footer — no brand line (the top admin header already
             carries "Aamar Malda Admin", so duplicating it here would
             read as visual noise). Keep this copy in lockstep with the
             matching block in layouts/site.blade.php and welcome.blade.php. --}}
        <footer class="mt-auto bg-stone-900 text-stone-300">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-5 flex flex-col sm:flex-row gap-3 sm:items-center sm:justify-between text-xs text-stone-400" data-testid="nic-credits">
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
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pb-4 -mt-2 flex items-center justify-between gap-3 text-[10px] text-stone-500">
                <span class="inline-flex items-center font-mono" data-testid="app-version-tag">{{ $appVersionTag ?? '' }}</span>
                <span data-testid="app-copyright">&copy; {{ now()->year }} {{ config('app.owner') }}. All rights reserved.</span>
            </div>
        </footer>
        <x-scroll-to-top />
        <x-cookie-banner />
    </body>
</html>
