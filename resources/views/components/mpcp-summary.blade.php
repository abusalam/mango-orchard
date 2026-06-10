@php
    $document = \App\Models\MpcpDocument::query()->find(1);
    $sections = \App\Models\MpcpSection::query()
        ->where('published', true)
        ->orderBy('display_order')
        ->withCount('entries')
        ->get();
@endphp

@if ($document && $sections->isNotEmpty())
    <section class="bg-emerald-50 dark:bg-stone-900 border-t border-emerald-100 dark:border-stone-800">
        <div class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-16 sm:py-20">
            <div class="text-center mb-10">
                <h2 class="text-3xl sm:text-4xl font-semibold tracking-tight text-emerald-900 dark:text-emerald-200">{{ $document->title_en }}</h2>
                @if ($document->title_bn)
                    <p class="mt-2 text-base text-emerald-800 dark:text-emerald-300">{{ $document->title_bn }}</p>
                @endif
                <div aria-hidden="true" class="mt-3 mx-auto w-16 h-0.5 bg-gradient-to-r from-emerald-500 to-amber-500"></div>
                @if ($document->about_md_en)
                    <p class="mt-4 max-w-2xl mx-auto text-stone-700 dark:text-stone-300 text-sm">{{ \Illuminate\Support\Str::limit(strip_tags(\Illuminate\Support\Str::markdown($document->about_md_en)), 280) }}</p>
                @endif
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 max-w-5xl mx-auto">
                @foreach ($sections as $section)
                    <a href="{{ route('mpcp.index') }}#{{ $section->slug }}"
                       class="bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 rounded-2xl p-5 hover:border-amber-400 dark:hover:border-amber-700 transition-colors block"
                       data-testid="mpcp-summary-section-{{ $section->slug }}">
                        <div class="flex items-start gap-3">
                            <span class="shrink-0 inline-flex items-center justify-center w-7 h-7 rounded-full bg-emerald-600 text-white text-xs font-semibold">{{ $section->display_order }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="font-medium text-stone-900 dark:text-stone-100 truncate">{{ $section->title_en }}</p>
                                @if ($section->title_bn)
                                    <p class="text-xs text-stone-500 dark:text-stone-400 truncate">{{ $section->title_bn }}</p>
                                @endif
                            </div>
                            <span class="shrink-0 inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700">{{ $section->entries_count }}</span>
                        </div>
                    </a>
                @endforeach
            </div>

            <div class="mt-8 text-center">
                <a href="{{ route('mpcp.index') }}" class="inline-flex items-center gap-2 px-5 py-3 rounded-full bg-emerald-700 text-white font-medium hover:bg-emerald-800 transition-colors" data-testid="mpcp-summary-cta">
                    View complete plan
                    <svg class="w-4 h-4" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 10h10M11 6l4 4-4 4" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>
        </div>
    </section>
@endif
