<x-site-layout :title="'New listing — Aamar Malda'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-8">
            <p class="text-sm text-stone-500 dark:text-stone-400"><a href="{{ route('my.listings.index') }}" class="hover:text-orange-700">My listings</a> / New</p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight">List a mango variety</h1>
            <p class="mt-2 text-stone-600 dark:text-stone-300">Tell buyers what you grow, when it's available, and how to reach you.</p>
        </header>

        <div class="rounded-2xl bg-white dark:bg-stone-950 border border-stone-200 dark:border-stone-800 shadow-sm p-6 sm:p-8">
            @include('my.listings._form', [
                'listing' => $listing,
                'varieties' => $varieties,
                'action' => route('my.listings.store'),
                'method' => 'POST',
            ])
        </div>
    </section>
</x-site-layout>
