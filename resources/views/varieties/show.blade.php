<x-site-layout :title="$variety->name.' — Aamar Malda'">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <nav class="text-sm text-stone-500 dark:text-stone-400 mb-6">
            <a href="{{ route('varieties.index') }}" class="hover:text-orange-700">Varieties</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ $variety->name }}</span>
        </nav>

        <div class="rounded-3xl overflow-hidden border border-stone-200 dark:border-stone-800 bg-white dark:bg-stone-950 shadow-sm">
            <div class="relative h-56 sm:h-72 overflow-hidden bg-gradient-to-br {{ $variety->gradient_classes }}">
                <div aria-hidden="true" class="absolute -bottom-12 -right-8 w-72 h-80 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                <div aria-hidden="true" class="absolute -top-10 -left-8 w-48 h-48 rounded-full bg-white/20 blur-2xl"></div>
                <div class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-medium {{ $variety->accent_classes }}">
                    Peak: {{ $variety->season }}
                </div>
            </div>
            <div class="p-8 sm:p-10">
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">{{ $variety->name }}</h1>
                <p class="mt-2 text-stone-500 dark:text-stone-400">{{ $variety->origin }}</p>
                <p class="mt-6 text-stone-800 dark:text-stone-200 leading-relaxed">{{ $variety->flavor }}</p>

                @if (! empty($variety->tags))
                    <div class="mt-6 flex flex-wrap gap-2">
                        @foreach ($variety->tags as $tag)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-800">{{ $tag }}</span>
                        @endforeach
                    </div>
                @endif

                <dl class="mt-8 grid grid-cols-2 gap-4 text-sm border-t border-stone-100 dark:border-stone-800 pt-6">
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">Season start</dt>
                        <dd class="font-medium">{{ \DateTime::createFromFormat('!m', (string) $variety->season_start)->format('F') }}</dd>
                    </div>
                    <div>
                        <dt class="text-stone-500 dark:text-stone-400">Season end</dt>
                        <dd class="font-medium">{{ \DateTime::createFromFormat('!m', (string) $variety->season_end)->format('F') }}</dd>
                    </div>
                </dl>

                @can(\App\Permissions::VARIETIES_MANAGE)
                    <div class="mt-8 pt-6 border-t border-stone-100 dark:border-stone-800 flex gap-3">
                        <a href="{{ route('varieties.edit', $variety) }}" class="inline-flex items-center px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">Edit</a>
                        <x-confirm-form
                            :action="route('varieties.destroy', $variety)"
                            method="DELETE"
                            :title="'Remove '.$variety->name.'?'"
                            message="This variety will be removed from the public catalogue. Existing listings referencing it will keep their data but lose the link."
                            confirm-label="Remove variety"
                        >
                            <button type="button" class="inline-flex items-center px-4 py-2 rounded-full border border-rose-300 text-rose-700 dark:text-rose-400 font-medium hover:bg-rose-50 transition-colors text-sm">Delete</button>
                        </x-confirm-form>
                    </div>
                @endcan
            </div>
        </div>

        {{-- Advisories targeting THIS variety (or any general advisory).
             Active-only filter; ordered urgent → warning → info. --}}
        @php
            $advisories = \App\Modules\MangoOrchard\Models\Advisory::query()
                ->with(['issuer', 'varieties'])
                ->active()
                ->where(function ($q) use ($variety) {
                    $q->whereHas('varieties', fn ($v) => $v->where('mango_varieties.id', $variety->id))
                      ->orWhereDoesntHave('varieties');
                })
                ->orderByRaw("CASE severity WHEN 'urgent' THEN 3 WHEN 'warning' THEN 2 ELSE 1 END DESC")
                ->latest('issued_at')
                ->limit(5)
                ->get();
        @endphp
        @if ($advisories->isNotEmpty())
            <div class="mt-10" data-testid="variety-advisories">
                <h2 class="text-lg font-semibold tracking-tight text-stone-900 dark:text-stone-100 mb-3">Advisories for {{ $variety->name }}</h2>
                <div class="space-y-3">
                    @foreach ($advisories as $advisory)
                        <x-advisory-card :advisory="$advisory" :compact="true" />
                    @endforeach
                </div>
            </div>
        @endif
    </section>
</x-site-layout>
