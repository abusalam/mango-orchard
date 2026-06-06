<x-site-layout :title="$listing->farm_name.' — Marketplace'">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('listings.index') }}" class="hover:text-orange-700">Marketplace</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800">{{ $listing->farm_name }}</span>
        </nav>

        <div class="rounded-3xl overflow-hidden border border-stone-200 bg-white shadow-sm">
            <div class="relative h-56 sm:h-80 overflow-hidden bg-gradient-to-br {{ $listing->variety->gradient_classes }}">
                @if ($listing->image_url)
                    <img src="{{ $listing->image_url }}" alt="{{ $listing->farm_name }}" loading="eager"
                         class="absolute inset-0 w-full h-full object-cover" data-testid="listing-show-image">
                @else
                    <div aria-hidden="true" class="absolute -bottom-12 -right-8 w-72 h-80 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                @endif
                @if ($listing->status === \App\Modules\MangoOrchard\Models\Listing::STATUS_SOLD_OUT)
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium bg-rose-100 text-rose-900 border border-rose-200">Sold out</span>
                @else
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium {{ $listing->variety->accent_classes }}">In season {{ $listing->variety->season }}</span>
                @endif
            </div>

            <div class="p-8 sm:p-10">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">{{ $listing->farm_name }}</h1>
                <p class="mt-2 text-stone-500">{{ $listing->location }}</p>

                <p class="mt-6">
                    <a href="{{ route('varieties.show', $listing->variety) }}" class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-stone-100 text-stone-800 text-sm hover:bg-stone-200 transition-colors">
                        🥭 {{ $listing->variety->name }}
                        <span class="text-stone-500 text-xs">— see variety details →</span>
                    </a>
                </p>

                @if ($listing->description)
                    <p class="mt-6 text-stone-800 leading-relaxed whitespace-pre-line">{{ $listing->description }}</p>
                @endif

                <dl class="mt-8 grid sm:grid-cols-2 gap-4 text-sm border-t border-stone-100 pt-6">
                    <div>
                        <dt class="text-stone-500">Available</dt>
                        <dd class="font-medium text-stone-800">
                            {{ \DateTime::createFromFormat('!m', (string) $listing->availability_start_month)->format('F') }}
                            to
                            {{ \DateTime::createFromFormat('!m', (string) $listing->availability_end_month)->format('F') }}
                        </dd>
                    </div>
                    @if ($listing->price_per_kg)
                        <div>
                            <dt class="text-stone-500">Price</dt>
                            <dd class="font-medium text-stone-800">₹{{ number_format((float) $listing->price_per_kg, 2) }} / kg</dd>
                        </div>
                    @endif
                    @if ($listing->quantity_available_kg)
                        <div>
                            <dt class="text-stone-500">Quantity available</dt>
                            <dd class="font-medium text-stone-800">~{{ number_format($listing->quantity_available_kg) }} kg</dd>
                        </div>
                    @endif
                    <div>
                        <dt class="text-stone-500">Listed by</dt>
                        <dd class="font-medium text-stone-800">{{ $listing->user->name }}</dd>
                    </div>
                </dl>

                @if ($listing->contact_email || $listing->contact_phone)
                    <div class="mt-8 pt-6 border-t border-stone-100">
                        <h2 class="text-sm font-semibold text-stone-800 mb-3">Get in touch</h2>
                        <div class="flex flex-wrap gap-3">
                            @if ($listing->contact_email)
                                <a href="mailto:{{ $listing->contact_email }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                                    {{ $listing->contact_email }}
                                </a>
                            @endif
                            @if ($listing->contact_phone)
                                <a href="tel:{{ preg_replace('/[^+0-9]/', '', $listing->contact_phone) }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-white text-stone-900 font-medium border border-stone-200 hover:border-stone-400 transition-colors text-sm">
                                    {{ $listing->contact_phone }}
                                </a>
                            @endif
                        </div>
                    </div>
                @endif

                @can('update', $listing)
                    <div class="mt-8 pt-6 border-t border-stone-100">
                        <a href="{{ route('my.listings.edit', $listing) }}" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">Edit this listing</a>
                    </div>
                @endcan
            </div>
        </div>
    </section>
</x-site-layout>
