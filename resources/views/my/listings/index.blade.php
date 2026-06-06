<x-site-layout :title="'My listings — Aamar Malda'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">My listings</h1>
                <p class="mt-2 text-stone-600">Mangoes you've put up for the marketplace. Drafts only show here; published listings appear at <a href="{{ route('listings.index') }}" class="text-orange-700 hover:text-orange-900">/listings</a>.</p>
            </div>
            @can(\App\Permissions::LISTINGS_MANAGE)
                <a href="{{ route('my.listings.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                    New listing
                </a>
            @endcan
        </header>

        @if ($listings->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                @can(\App\Permissions::LISTINGS_MANAGE)
                    <p class="text-stone-600">You haven't listed anything yet.</p>
                    <a href="{{ route('my.listings.create') }}" class="mt-4 inline-block text-orange-700 font-medium">List your first variety →</a>
                @else
                    <p class="text-stone-600">You don't have any listings.</p>
                    <p class="mt-2 text-sm text-stone-500">Listing requires the <code class="px-1.5 py-0.5 bg-stone-100 rounded text-stone-700">grower</code> role — ask an admin to grant it.</p>
                @endcan
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($listings as $listing)
                    <article class="relative overflow-hidden rounded-2xl bg-white border border-stone-200 hover:shadow-lg transition-shadow">
                        <div class="relative h-32 overflow-hidden bg-gradient-to-br {{ $listing->variety->gradient_classes }}">
                            <span @class([
                                'absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium',
                                'bg-emerald-100 text-emerald-900 border border-emerald-200' => $listing->status === \App\Modules\MangoOrchard\Models\Listing::STATUS_PUBLISHED,
                                'bg-stone-100 text-stone-700 border border-stone-200' => $listing->status === \App\Modules\MangoOrchard\Models\Listing::STATUS_DRAFT,
                                'bg-rose-100 text-rose-900 border border-rose-200' => $listing->status === \App\Modules\MangoOrchard\Models\Listing::STATUS_SOLD_OUT,
                            ])>{{ Str::headline($listing->status) }}</span>
                        </div>
                        <div class="p-5">
                            <h2 class="text-lg font-semibold tracking-tight">{{ $listing->farm_name }}</h2>
                            <p class="mt-1 text-sm text-stone-500">{{ $listing->variety->name }} · {{ $listing->location }}</p>
                            @if ($listing->price_per_kg)
                                <p class="mt-3 text-sm text-stone-800">₹{{ number_format((float) $listing->price_per_kg, 2) }} / kg</p>
                            @endif
                            <div class="mt-5 flex gap-2 text-xs">
                                <a href="{{ route('my.listings.edit', $listing) }}" class="px-2.5 py-1 rounded border border-stone-200 hover:border-stone-400 transition-colors">Edit</a>
                                @if ($listing->status !== \App\Modules\MangoOrchard\Models\Listing::STATUS_DRAFT)
                                    <a href="{{ route('listings.show', $listing) }}" target="_blank" rel="noopener" class="px-2.5 py-1 rounded border border-stone-200 hover:border-stone-400 transition-colors">View public →</a>
                                @endif
                                <x-confirm-form
                                    :action="route('my.listings.destroy', $listing)"
                                    method="DELETE"
                                    title="Remove this listing?"
                                    :message="'It will disappear from your dashboard and from the public marketplace immediately. This cannot be undone.'"
                                    confirm-label="Delete listing"
                                    class="inline ml-auto"
                                >
                                    <button type="button" class="px-2.5 py-1 rounded border border-rose-200 text-rose-700 hover:border-rose-400 transition-colors">Delete</button>
                                </x-confirm-form>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-site-layout>
