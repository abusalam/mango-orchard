<x-site-layout :title="$advisory->title.' — Advisory'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('advisories.index') }}" class="hover:text-orange-700">Advisories</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800">{{ $advisory->title }}</span>
        </nav>

        @php
            $severityBg = match ($advisory->severity) {
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-50 border-rose-300',
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-50 border-amber-300',
                default => 'bg-white border-stone-200',
            };
            $severityChip = match ($advisory->severity) {
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_URGENT => 'bg-rose-200 text-rose-900 border-rose-300',
                \App\Modules\MangoOrchard\Models\Advisory::SEVERITY_WARNING => 'bg-amber-200 text-amber-900 border-amber-300',
                default => 'bg-stone-100 text-stone-700 border-stone-200',
            };
        @endphp

        <article class="rounded-2xl border {{ $severityBg }} p-6 sm:p-8" data-testid="advisory-detail">
            <div class="flex flex-wrap items-center gap-2 mb-4">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium border {{ $severityChip }}">{{ \App\Modules\MangoOrchard\Models\Advisory::SEVERITIES[$advisory->severity] }}</span>
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-700 border border-stone-200">{{ \App\Modules\MangoOrchard\Models\Advisory::CATEGORIES[$advisory->category] }}</span>
                @if (! $advisory->published)
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-200 text-stone-700 border border-stone-300">Draft</span>
                @endif
                @if ($advisory->isExpired())
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-200 text-stone-700 border border-stone-300">Expired</span>
                @endif
            </div>

            <h1 class="text-3xl font-semibold tracking-tight text-stone-900">{{ $advisory->title }}</h1>

            @if ($advisory->image_url)
                <img src="{{ $advisory->image_url }}" alt="{{ $advisory->title }}" loading="eager"
                     class="mt-5 w-full max-h-96 object-cover rounded-xl border border-stone-200" data-testid="advisory-show-image">
            @endif

            <dl class="mt-4 grid sm:grid-cols-2 gap-2 text-xs text-stone-500 border-y border-stone-200 py-3">
                <div>
                    <dt class="font-medium text-stone-600">Issued</dt>
                    <dd>{{ $advisory->issued_at?->toFormattedDateString() ?? '—' }} @if ($advisory->issuer) by <strong class="text-stone-700">{{ $advisory->issuer->name }}</strong> @endif</dd>
                </div>
                @if ($advisory->expires_at)
                    <div>
                        <dt class="font-medium text-stone-600">Valid until</dt>
                        <dd>{{ $advisory->expires_at->toFormattedDateString() }}</dd>
                    </div>
                @endif
                <div class="sm:col-span-2">
                    <dt class="font-medium text-stone-600">Targets</dt>
                    <dd>
                        @if ($advisory->isGeneral())
                            All varieties
                        @else
                            @foreach ($advisory->varieties as $variety)
                                <a href="{{ route('varieties.show', $variety) }}" class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 text-stone-800 border border-stone-200 hover:bg-stone-200 mr-1">{{ $variety->name }}</a>
                            @endforeach
                        @endif
                    </dd>
                </div>
            </dl>

            <div class="mt-5 text-stone-800 leading-relaxed whitespace-pre-line">{{ $advisory->body }}</div>

            @can(\App\Permissions::ADVISORIES_MANAGE)
                <div class="mt-6 pt-4 border-t border-stone-200 flex items-center gap-3">
                    <a href="{{ route('admin.advisories.edit', $advisory) }}" class="text-sm text-orange-700 hover:text-orange-900 font-medium">Edit advisory →</a>
                    <a href="{{ route('admin.advisories.index') }}" class="text-sm text-stone-500 hover:text-stone-900">All advisories</a>
                </div>
            @endcan
        </article>
    </section>
</x-site-layout>
