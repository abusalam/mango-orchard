<x-site-layout :title="'Edit '.$advisory->title.' — Admin'">
    <section class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8 py-12">
        <nav class="text-sm text-stone-500 mb-6">
            <a href="{{ route('admin.advisories.index') }}" class="hover:text-orange-700">All advisories</a>
            <span class="mx-2">/</span>
            <span class="text-stone-800">{{ Str::limit($advisory->title, 60) }}</span>
        </nav>

        <h1 class="text-3xl font-semibold tracking-tight mb-6">Edit advisory</h1>

        @include('admin.advisories._form', [
            'advisory' => $advisory,
            'varieties' => $varieties,
            'selectedVarietyIds' => $selectedVarietyIds,
            'action' => route('admin.advisories.update', $advisory),
            'method' => 'PUT',
        ])

        <x-confirm-form
            :action="route('admin.advisories.destroy', $advisory)"
            method="DELETE"
            :title="'Delete advisory \''.Str::limit($advisory->title, 60).'\'?'"
            message="This permanently removes the advisory and its variety targeting. Cannot be undone."
            confirm-label="Delete advisory"
            class="mt-10 pt-6 border-t border-stone-100 block"
        >
            <button type="button" class="text-sm text-rose-700 hover:text-rose-900">Delete this advisory</button>
        </x-confirm-form>
    </section>
</x-site-layout>
