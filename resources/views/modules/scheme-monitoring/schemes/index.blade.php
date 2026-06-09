<x-site-layout :title="'Schemes — Aamar Malda'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-8">
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
        <header class="mb-6 flex flex-wrap justify-between items-end gap-3">
            <h1 class="text-3xl font-semibold tracking-tight">Schemes</h1>
            <a href="{{ route('monitoring.schemes.create') }}" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">New scheme</a>
        </header>

        {{-- Search bar — matches name / abbreviation / description (ILIKE). --}}
        <form method="GET" action="{{ route('monitoring.schemes.index') }}" class="mb-4 flex flex-wrap items-center gap-2" data-testid="schemes-search-form">
            <div class="relative flex-1 min-w-[14rem]">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-stone-400 pointer-events-none" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <circle cx="11" cy="11" r="8"/>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                </svg>
                <input
                    type="search"
                    name="q"
                    value="{{ $search }}"
                    placeholder="Search schemes by name, abbreviation, or description"
                    class="block w-full pl-10 pr-3 py-2 rounded-full border-stone-300 bg-white dark:bg-stone-950 text-sm focus:border-orange-400 focus:ring-orange-400"
                    data-testid="schemes-search-input"
                >
            </div>
            <button type="submit" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-800 dark:text-stone-200 hover:bg-stone-200 dark:hover:bg-stone-600 text-sm font-medium">Search</button>
            @if ($search !== '')
                <a href="{{ route('monitoring.schemes.index') }}" class="text-xs text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100 underline" data-testid="schemes-search-clear">Clear</a>
            @endif
        </form>
        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
            @if ($schemes->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400" data-testid="schemes-empty">
                    @if ($search !== '')
                        No schemes match &ldquo;{{ $search }}&rdquo;.
                    @else
                        No schemes yet.
                    @endif
                </p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                        <tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Owner</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Tasks</th><th></th></tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        @foreach ($schemes as $scheme)
                            <tr class="odd:bg-white dark:odd:bg-stone-950 dark:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="scheme-row-{{ $scheme->id }}">
                                <td class="px-4 py-3 font-medium"><a href="{{ route('monitoring.schemes.show', $scheme) }}" class="hover:underline">{{ $scheme->name }}</a></td>
                                <td class="px-4 py-3 text-stone-600 dark:text-stone-300">{{ $scheme->owner?->name }}</td>
                                <td class="px-4 py-3"><span class="inline-flex px-2 py-0.5 rounded-full text-xs bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300">{{ \App\Modules\SchemeMonitoring\Models\Scheme::STATUSES[$scheme->status] }}</span></td>
                                <td class="px-4 py-3 text-stone-600 dark:text-stone-300">{{ $scheme->tasks->count() }}</td>
                                <td class="px-4 py-3 text-right text-xs"><a href="{{ route('monitoring.schemes.edit', $scheme) }}" class="text-stone-700 dark:text-stone-100 hover:underline">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <div class="mt-4">{{ $schemes->links() }}</div>
    </section>
</x-site-layout>
