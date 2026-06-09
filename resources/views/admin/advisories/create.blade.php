<x-site-layout :title="'New advisory — Admin'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('admin.advisories.index') }}" class="hover:text-orange-700">All advisories</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">New</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight mb-6">Issue an advisory</h1>

        @include('admin.advisories._form', [
            'advisory' => $advisory,
            'varieties' => $varieties,
            'selectedVarietyIds' => $selectedVarietyIds,
            'action' => route('admin.advisories.store'),
            'method' => 'POST',
        ])
    </section>
</x-site-layout>
