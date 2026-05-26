<x-site-layout :title="'New event — Admin'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('admin.events.index') }}" class="hover:text-orange-700">All events</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800">New event</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight mb-6">Post a new training event</h1>

        @include('admin.events._form', [
            'event' => $event,
            'action' => route('admin.events.store'),
            'method' => 'POST',
        ])
    </section>
</x-site-layout>
