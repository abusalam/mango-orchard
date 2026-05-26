<x-site-layout :title="'Advisories — Mango Orchard'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-medium tracking-wide uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                Orchard advisories
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl font-semibold tracking-tight">Seasonal alerts, best practices, and pest warnings</h1>
            <p class="mt-3 text-stone-600 max-w-2xl">Issued by advisors for the growers and curators of the orchard community. Filter by variety if you only want what affects what you grow.</p>
            @can(\App\Permissions::ADVISORIES_MANAGE)
                <div class="mt-5">
                    <a href="{{ route('admin.advisories.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">Issue an advisory →</a>
                </div>
            @endcan
        </header>

        <form method="GET" action="{{ route('advisories.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
            <label for="category" class="text-sm text-stone-700">Category:</label>
            <select name="category" id="category" onchange="this.form.submit()"
                    class="rounded-lg border-stone-300 text-sm focus:border-orange-400 focus:ring-orange-400">
                <option value="">All categories</option>
                @foreach (\App\Models\Advisory::CATEGORIES as $value => $label)
                    <option value="{{ $value }}" @selected($filterCategory === $value)>{{ $label }}</option>
                @endforeach
            </select>

            <label for="variety" class="text-sm text-stone-700">Variety:</label>
            <select name="variety" id="variety" onchange="this.form.submit()"
                    class="rounded-lg border-stone-300 text-sm focus:border-orange-400 focus:ring-orange-400">
                <option value="">All varieties</option>
                @foreach ($varieties as $variety)
                    <option value="{{ $variety->id }}" @selected($filterVarietyId === $variety->id)>{{ $variety->name }}</option>
                @endforeach
            </select>

            @if ($filterCategory !== '' || $filterVarietyId > 0)
                <a href="{{ route('advisories.index') }}" class="text-xs text-stone-500 hover:text-stone-900">Clear filters</a>
            @endif
            <p class="ml-auto text-sm text-stone-500">{{ $advisories->total() }} {{ Str::plural('advisory', $advisories->total()) }}</p>
        </form>

        @if ($advisories->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                <p class="text-stone-600">No advisories match those filters right now.</p>
            </div>
        @else
            <div class="space-y-4" data-testid="advisories-list">
                @foreach ($advisories as $advisory)
                    <x-advisory-card :advisory="$advisory" />
                @endforeach
            </div>

            <div class="mt-8">
                {{ $advisories->links() }}
            </div>
        @endif
    </section>
</x-site-layout>
