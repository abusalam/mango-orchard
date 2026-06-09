<x-site-layout :title="'Edit '.$event->title.' — Admin'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('admin.events.index') }}" class="hover:text-orange-700">All events</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ $event->title }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight mb-6">Edit event</h1>

        @include('admin.events._form', [
            'event' => $event,
            'action' => route('admin.events.update', $event),
            'method' => 'PUT',
        ])

        <x-confirm-form
            :action="route('admin.events.destroy', $event)"
            method="DELETE"
            :title="'Permanently delete '.$event->title.'?'"
            message="The event will disappear from the public listings and from anyone's calendar links. This cannot be undone."
            confirm-label="Delete event"
            class="mt-10 pt-6 border-t border-stone-100 block"
        >
            <button type="button" class="text-sm text-rose-700 hover:text-rose-900">Delete this event</button>
        </x-confirm-form>
    </section>
</x-site-layout>
