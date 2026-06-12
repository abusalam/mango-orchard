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

        {{-- Divider + spacing live on a wrapper div: the confirm-form
             component defaults to display:inline, and stacking `block` on
             top loses to `inline` in Tailwind's output order — leaving an
             inline element whose border-t paints upward through the Save
             button above. --}}
        <div class="mt-10 pt-6 border-t border-stone-100 dark:border-stone-800">
            <x-confirm-form
                :action="route('admin.events.destroy', $event)"
                method="DELETE"
                :title="'Permanently delete '.$event->title.'?'"
                message="The event will disappear from the public listings and from anyone's calendar links. This cannot be undone."
                confirm-label="Delete event"
            >
                <button type="button" class="text-sm text-rose-700 dark:text-rose-400 hover:text-rose-900">Delete this event</button>
            </x-confirm-form>
        </div>
    </section>
</x-site-layout>
