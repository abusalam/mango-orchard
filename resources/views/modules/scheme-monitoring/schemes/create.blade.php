<x-site-layout :title="'New scheme — '.config('app.name')">
    <section class="mx-auto max-w-2xl px-4 sm:px-6 lg:px-8 py-8">
        <a
            href="{{ route('monitoring.dashboard') }}"
            class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 mb-3"
            data-testid="back-to-dashboard"
        >
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            Pragati Darpan dashboard
        </a>
        <h1 class="text-3xl font-semibold tracking-tight mb-6">New scheme</h1>
        <form method="POST" action="{{ route('monitoring.schemes.store') }}" class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 p-6">
            @include('scheme-monitoring::schemes._form')
        </form>
    </section>
</x-site-layout>
