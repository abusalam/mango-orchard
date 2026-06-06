<x-site-layout :title="'Edit listing — Aamar Malda'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12 sm:py-16">
        <header class="mb-8">
            <p class="text-sm text-stone-500"><a href="{{ route('my.listings.index') }}" class="hover:text-orange-700">My listings</a> / Edit</p>
            <h1 class="mt-2 text-3xl sm:text-4xl font-semibold tracking-tight">Edit listing</h1>
            <p class="mt-2 text-stone-600">{{ $listing->farm_name }} · {{ $listing->variety->name }}</p>
        </header>

        <div class="rounded-2xl bg-white border border-stone-200 shadow-sm p-6 sm:p-8">
            @include('my.listings._form', [
                'listing' => $listing,
                'varieties' => $varieties,
                'action' => route('my.listings.update', $listing),
                'method' => 'PUT',
            ])
        </div>
    </section>
</x-site-layout>
