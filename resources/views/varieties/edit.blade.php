<x-site-layout :title="'Edit '.$variety->name.' — Aamar Malda'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-8">
            <p class="text-sm text-stone-500 dark:text-stone-400">
                <a href="{{ route('varieties.index') }}" class="hover:text-orange-700">Varieties</a> /
                <a href="{{ route('varieties.show', $variety) }}" class="hover:text-orange-700">{{ $variety->name }}</a> /
                Edit
            </p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight">Edit {{ $variety->name }}</h1>
        </header>

        <div class="rounded-2xl bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 shadow-sm p-6 sm:p-8">
            @include('varieties._form', [
                'variety' => $variety,
                'action' => route('varieties.update', $variety),
                'method' => 'PUT',
            ])
        </div>
    </section>
</x-site-layout>
