<x-site-layout :title="'Edit '.$advisory->title.' — Admin'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('admin.advisories.index') }}" class="hover:text-orange-700">All advisories</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ Str::limit($advisory->title, 60) }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight mb-6">Edit advisory</h1>

        @include('admin.advisories._form', [
            'advisory' => $advisory,
            'varieties' => $varieties,
            'selectedVarietyIds' => $selectedVarietyIds,
            'action' => route('admin.advisories.update', $advisory),
            'method' => 'PUT',
        ])

        {{-- Divider + spacing on a wrapper div — the confirm-form component
             is display:inline and `block` loses to `inline` in Tailwind's
             output order, leaving an inline border-t that paints upward
             through the Save button above. --}}
        <div class="mt-10 pt-6 border-t border-stone-100 dark:border-stone-800">
            <x-confirm-form
                :action="route('admin.advisories.destroy', $advisory)"
                method="DELETE"
                :title="'Delete advisory \''.Str::limit($advisory->title, 60).'\'?'"
                message="This permanently removes the advisory and its variety targeting. Cannot be undone."
                confirm-label="Delete advisory"
            >
                <button type="button" class="text-sm text-rose-700 dark:text-rose-400 hover:text-rose-900">Delete this advisory</button>
            </x-confirm-form>
        </div>
    </section>
</x-site-layout>
