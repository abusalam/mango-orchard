<x-site-layout :title="$advisory->title.' — Advisory'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400 mb-6">
            <a href="{{ route('advisories.index') }}" class="hover:text-orange-700">Advisories</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ $advisory->title }}</span>
        </nav>

        @php
            // Severity cards fall back to stone-900 in dark mode; the
            // colored border carries the urgency signal (matches the
            // <x-advisory-card /> component used on the index pages).
            $severityBg = match ($advisory->severity) {
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-50 dark:bg-stone-900 border-rose-300 dark:border-rose-700',
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-50 dark:bg-stone-900 border-amber-300 dark:border-amber-700',
                default => 'bg-white dark:bg-stone-950 border-stone-200 dark:border-stone-800',
            };
            $severityChip = match ($advisory->severity) {
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-200 text-rose-900 border-rose-300',
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-200 text-amber-900 border-amber-300',
                default => 'bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border-stone-200 dark:border-stone-800',
            };
        @endphp

        <article class="rounded-2xl border {{ $severityBg }} p-6 sm:p-8" data-testid="advisory-detail">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $severityChip }}">{{ \App\Modules\MangoOrchard\Models\Advisory::SEVERITIES[$advisory->severity] }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-800">{{ \App\Modules\MangoOrchard\Models\Advisory::CATEGORIES[$advisory->category] }}</span>
                @if (! $advisory->published)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300">Draft</span>
                @endif
                @if ($advisory->isExpired())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-200 text-stone-700 dark:text-stone-300 border border-stone-300">Expired</span>
                @endif
            </div>

            <h1 class="text-3xl font-semibold tracking-tight text-stone-900 dark:text-stone-100">{{ $advisory->title }}</h1>

            @if ($advisory->image_url)
                <img src="{{ $advisory->image_url }}" alt="{{ $advisory->title }}" loading="eager"
                     class="mt-5 w-full max-h-96 object-cover rounded-xl border border-stone-200 dark:border-stone-800" data-testid="advisory-show-image">
            @endif

            <dl class="mt-4 grid sm:grid-cols-2 gap-2 text-xs text-stone-500 dark:text-stone-400 border-y border-stone-200 dark:border-stone-800 py-3">
                <div>
                    <dt class="font-medium text-stone-600 dark:text-stone-300">Issued</dt>
                    <dd>{{ $advisory->issued_at?->toFormattedDateString() ?? '—' }} @if ($advisory->issuer) by <strong class="text-stone-700 dark:text-stone-300">{{ $advisory->issuer->name }}</strong> @endif</dd>
                </div>
                @if ($advisory->expires_at)
                    <div>
                        <dt class="font-medium text-stone-600 dark:text-stone-300">Valid until</dt>
                        <dd>{{ $advisory->expires_at->toFormattedDateString() }}</dd>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <dt class="font-medium text-stone-600 dark:text-stone-300">Targets</dt>
                    <dd>
                        @if ($advisory->isGeneral())
                            All varieties
                        @else
                            @foreach ($advisory->varieties as $variety)
                                <a href="{{ route('varieties.show', $variety) }}" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-800 dark:text-stone-200 border border-stone-200 dark:border-stone-800 hover:bg-stone-200 dark:hover:bg-stone-600 mr-1">{{ $variety->name }}</a>
                            @endforeach
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-5 text-stone-800 dark:text-stone-200 leading-relaxed whitespace-pre-line">{{ $advisory->body }}</div>

            @can(\App\Permissions::ADVISORIES_MANAGE)
                <div class="mt-6 pt-4 border-t border-stone-200 dark:border-stone-800 flex items-center gap-3">
                    <a href="{{ route('admin.advisories.edit', $advisory) }}" class="text-sm text-orange-700 hover:text-orange-900 font-medium">Edit advisory →</a>
                    <a href="{{ route('admin.advisories.index') }}" class="text-sm text-stone-500 dark:text-stone-400 hover:text-stone-900 dark:text-stone-100">All advisories</a>
                </div>
            @endcan
        </article>
    </section>
</x-site-layout>
