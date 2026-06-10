<x-site-layout :title="$document->title_en">
    <main class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-10 sm:py-14 space-y-14">

        {{-- Document header --}}
        <header class="text-center">
            <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight text-emerald-900 dark:text-emerald-200">{{ $document->title_en }}</h1>
            @if ($document->title_bn)
                <p class="mt-2 text-xl sm:text-2xl text-emerald-800 dark:text-emerald-300">{{ $document->title_bn }}</p>
            @endif
            <div aria-hidden="true" class="mt-3 mx-auto w-16 h-0.5 bg-gradient-to-r from-emerald-500 to-amber-500"></div>

            @if ($document->attribution_md_en)
                <div class="mt-6 mx-auto max-w-2xl text-sm text-stone-700 dark:text-stone-200">
                    {!! Str::markdown($document->attribution_md_en) !!}
                </div>
            @endif
        </header>

        {{-- About --}}
        @if ($document->about_md_en || $document->about_md_bn)
            <section class="grid md:grid-cols-2 gap-6">
                @if ($document->about_md_en)
                    <div class="text-stone-700 dark:text-stone-300 text-sm leading-relaxed">
                        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100 mb-2">About this Communication Plan</h2>
                        {!! Str::markdown($document->about_md_en) !!}
                    </div>
                @endif
                @if ($document->about_md_bn)
                    <div class="text-stone-700 dark:text-stone-300 text-sm leading-relaxed">
                        <h2 class="text-base font-semibold text-stone-900 dark:text-stone-100 mb-2">এই যোগাযোগ পরিকল্পনা সম্পর্কে</h2>
                        {!! Str::markdown($document->about_md_bn) !!}
                    </div>
                @endif
            </section>
        @endif

        {{-- Sections --}}
        @foreach ($sections as $section)
            <section id="{{ $section->slug }}" class="space-y-5" data-testid="mpcp-section-{{ $section->slug }}">
                <header class="border-b border-stone-200 dark:border-stone-800 pb-3">
                    <div class="flex items-baseline gap-3 flex-wrap">
                        <span class="inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-semibold">{{ $section->display_order }}</span>
                        <div class="min-w-0">
                            <h2 class="text-xl sm:text-2xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $section->title_en }}</h2>
                            @if ($section->title_bn)
                                <p class="text-sm text-stone-600 dark:text-stone-400">{{ $section->title_bn }}</p>
                            @endif
                        </div>
                        <span class="ml-auto text-xs text-stone-500 dark:text-stone-400">{{ $section->entries->count() }} {{ Str::plural('entry', $section->entries->count()) }}</span>
                    </div>
                    {{-- intro_md_en intentionally not rendered publicly. It carries
                         methodological / provenance notes (e.g. "Source: ...",
                         "Aadhaar numbers omitted...") that belong in the admin
                         context only. Admins still see it on /admin/mpcp/sections/{slug}/edit. --}}
                </header>

                @if ($section->layout === 'table')
                    {{-- Each entry → generic card with columns rendered as labelled rows. --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                        @foreach ($section->entries as $entry)
                            <x-mpcp.entry-card
                                :columns="$section->columns"
                                :data="$entry->data"
                                :position="$entry->position"
                            />
                        @endforeach
                    </div>
                @else
                    {{-- Card sections (§§5, 7) — single structured contact card per entry,
                         parsed in MpcpController via ContactCardParser. --}}
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 max-w-4xl">
                        @foreach ($section->entries as $entry)
                            @php $card = $sectionCards[$entry->id] ?? null; @endphp
                            @if ($card)
                                <x-mpcp.contact-card
                                    :name="$card['name']"
                                    :designation="$card['designation']"
                                    :organization="$card['organization']"
                                    :address="$card['address']"
                                    :phone="$card['phone']"
                                    :email="$card['email']"
                                    :note="$card['note']"
                                />
                            @endif
                        @endforeach
                    </div>
                @endif
            </section>
        @endforeach

        {{-- Footer — Prepared by Malda District Administration --}}
        @if (! empty($footerCards))
            <section class="pt-10 border-t border-stone-200 dark:border-stone-800" data-testid="mpcp-footer">
                <header class="text-center mb-8">
                    <h2 class="text-2xl font-semibold tracking-tight text-emerald-900 dark:text-emerald-200">Prepared by — Malda District Administration</h2>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">মালদা জেলা প্রশাসন</p>
                </header>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-4xl mx-auto">
                    @foreach ($footerCards as $card)
                        {{-- Show the role as a pill only when it's short and adds info
                             beyond the designation (e.g. "NODAL OFFICER"). Long roles
                             like "District Magistrate & Collector" overlap the
                             designation line below; pill is suppressed there. --}}
                        <x-mpcp.contact-card
                            :name="$card['name']"
                            :designation="$card['designation']"
                            :organization="$card['organization']"
                            :address="$card['address']"
                            :phone="$card['phone']"
                            :email="$card['email']"
                            :note="$card['note']"
                            :role-label="mb_strlen($card['role_en']) <= 18 ? mb_strtoupper($card['role_en']) : null"
                        />
                    @endforeach
                </div>
            </section>
        @endif
    </main>
</x-site-layout>
