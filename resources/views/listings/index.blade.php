<x-site-layout :title="'Marketplace — Mango Orchard'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-10">
            <span class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-100 text-amber-900 text-xs font-medium tracking-wide uppercase">
                <span class="w-1.5 h-1.5 rounded-full bg-amber-600"></span>
                Grower marketplace
            </span>
            <h1 class="mt-4 text-3xl sm:text-4xl font-semibold tracking-tight">Mangoes from farmers near and far</h1>
            <p class="mt-3 text-stone-600 max-w-2xl">Listings posted by orchard owners. Reach out to them directly using the contact details on each listing.</p>
            @guest
                <div class="mt-5">
                    <a href="{{ route('register') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500 text-stone-900 font-medium hover:bg-amber-400 transition-colors text-sm">
                        Sign up →
                    </a>
                </div>
            @else
                @can(\App\Permissions::LISTINGS_MANAGE)
                    <div class="mt-5">
                        <a href="{{ route('my.listings.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-amber-500 text-stone-900 font-medium hover:bg-amber-400 transition-colors text-sm">
                            List your own harvest →
                        </a>
                    </div>
                @else
                    <p class="mt-5 text-sm text-stone-500">Want to list your own mangoes? Ask an admin to grant you the <code class="px-1.5 py-0.5 bg-stone-100 rounded text-stone-700">grower</code> role.</p>
                @endcan
            @endguest
        </header>

        <form method="GET" action="{{ route('listings.index') }}" class="mb-6 flex flex-wrap items-center gap-3">
            <label for="variety" class="text-sm text-stone-700">Filter by variety:</label>
            <select name="variety" id="variety" onchange="this.form.submit()"
                    class="rounded-lg border-stone-300 text-sm focus:border-orange-400 focus:ring-orange-400">
                <option value="">All varieties</option>
                @foreach ($varieties as $variety)
                    <option value="{{ $variety->id }}" @selected($filterVarietyId === $variety->id)>{{ $variety->name }}</option>
                @endforeach
            </select>
            @if ($filterVarietyId)
                <a href="{{ route('listings.index') }}" class="text-xs text-stone-500 hover:text-stone-900">Clear</a>
            @endif
            <p class="ml-auto text-sm text-stone-500">{{ $listings->total() }} {{ Str::plural('listing', $listings->total()) }}</p>
        </form>

        @if ($listings->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                <p class="text-stone-600">No listings here yet.</p>
                @can(\App\Permissions::LISTINGS_MANAGE)
                    <a href="{{ route('my.listings.create') }}" class="mt-4 inline-block text-orange-700 font-medium">Be the first to list →</a>
                @endcan
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($listings as $listing)
                    <article class="group relative overflow-hidden rounded-2xl bg-white border border-stone-200/80 hover:border-stone-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <a href="{{ route('listings.show', $listing) }}" class="block">
                            <div class="relative h-40 overflow-hidden bg-gradient-to-br {{ $listing->variety->gradient_classes }}">
                                @if ($listing->image_url)
                                    <img src="{{ $listing->image_url }}" alt="{{ $listing->farm_name }}" loading="lazy"
                                         class="absolute inset-0 w-full h-full object-cover" data-testid="listing-card-image">
                                @else
                                    <div aria-hidden="true" class="absolute -bottom-10 -right-6 w-44 h-52 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                                @endif
                                @if ($listing->status === \App\Models\Listing::STATUS_SOLD_OUT)
                                    <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium bg-rose-100 text-rose-900 border border-rose-200">Sold out</span>
                                @else
                                    <span class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $listing->variety->accent_classes }}">{{ $listing->variety->season }}</span>
                                @endif
                            </div>
                            <div class="p-5">
                                <h2 class="text-lg font-semibold tracking-tight">{{ $listing->farm_name }}</h2>
                                <p class="mt-1 text-sm text-stone-500">{{ $listing->variety->name }} · {{ $listing->location }}</p>
                                @if ($listing->price_per_kg)
                                    <p class="mt-3 text-sm font-medium text-stone-800">₹{{ number_format((float) $listing->price_per_kg, 2) }} <span class="text-stone-500 font-normal">/ kg</span></p>
                                @endif
                                @if ($listing->quantity_available_kg)
                                    <p class="text-xs text-stone-500">~{{ number_format($listing->quantity_available_kg) }} kg available</p>
                                @endif
                            </div>
                        </a>
                    </article>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $listings->links() }}
            </div>
        @endif
    </section>
</x-site-layout>
