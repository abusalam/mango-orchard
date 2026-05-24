<x-site-layout :title="'Varieties — Mango Orchard'">
    <section class="mx-auto max-w-6xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-4 mb-10">
            <div>
                <h1 class="text-3xl sm:text-4xl font-semibold tracking-tight">All mango varieties</h1>
                <p class="mt-2 text-stone-600">Browse the {{ $varieties->count() }} {{ Str::plural('variety', $varieties->count()) }} in the orchard.</p>
            </div>
            @can(\App\Permissions::VARIETIES_MANAGE)
                <a href="{{ route('varieties.create') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-stone-900 text-amber-50 font-medium hover:bg-stone-800 transition-colors text-sm">
                    New variety
                </a>
            @endcan
        </div>

        @if ($varieties->isEmpty())
            <div class="rounded-2xl border border-dashed border-stone-300 p-12 text-center">
                <p class="text-stone-600">No varieties yet.</p>
                @can(\App\Permissions::VARIETIES_MANAGE)
                    <a href="{{ route('varieties.create') }}" class="mt-4 inline-block text-orange-700 font-medium">Add the first one.</a>
                @endcan
            </div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($varieties as $variety)
                    <article class="group relative overflow-hidden rounded-2xl bg-white border border-stone-200/80 hover:border-stone-300 hover:shadow-xl hover:-translate-y-1 transition-all duration-300">
                        <a href="{{ route('varieties.show', $variety) }}" class="block">
                            <div class="relative h-40 overflow-hidden bg-gradient-to-br {{ $variety->gradient_classes }}">
                                <div aria-hidden="true" class="absolute -bottom-10 -right-6 w-44 h-52 rounded-[55%_45%_55%_45%/60%_55%_45%_40%] bg-white/15 rotate-12"></div>
                                <div aria-hidden="true" class="absolute -top-8 -left-6 w-32 h-32 rounded-full bg-white/20 blur-xl"></div>
                                <div class="absolute top-3 right-3 px-2.5 py-1 rounded-full text-[11px] font-medium {{ $variety->accent_classes }}">
                                    {{ $variety->season }}
                                </div>
                            </div>
                            <div class="p-5">
                                <h2 class="text-lg font-semibold tracking-tight">{{ $variety->name }}</h2>
                                <p class="mt-1 text-sm text-stone-500">{{ $variety->origin }}</p>
                            </div>
                        </a>
                        @can(\App\Permissions::VARIETIES_MANAGE)
                            <div class="px-5 pb-5 flex gap-2 text-xs">
                                <a href="{{ route('varieties.edit', $variety) }}" class="px-2.5 py-1 rounded border border-stone-200 hover:border-stone-400 transition-colors">Edit</a>
                                <form method="POST" action="{{ route('varieties.destroy', $variety) }}" onsubmit="return confirm('Remove {{ $variety->name }}?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="px-2.5 py-1 rounded border border-rose-200 text-rose-700 hover:border-rose-400 transition-colors">Delete</button>
                                </form>
                            </div>
                        @endcan
                    </article>
                @endforeach
            </div>
        @endif
    </section>
</x-site-layout>
