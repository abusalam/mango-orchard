<x-admin-layout title="MPCP" active="mpcp">
    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Mango Promotion Communication Plan</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">{{ $document->title_en }} · {{ $document->title_bn }}</p>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('admin.mpcp.document.edit') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-100 hover:border-stone-400 text-sm" data-testid="edit-document">Document chrome</a>
            <a href="{{ route('admin.mpcp.sections.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm" data-testid="new-section">New section</a>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100" data-testid="flash-status">{{ session('status') }}</div>
    @endif

    <section data-testid="mpcp-sections">
        <h2 class="text-lg font-semibold text-stone-900 dark:text-stone-100 mb-3">Sections</h2>
        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
            @if ($sections->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No sections yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium w-12">#</th>
                            <th class="px-4 py-2 font-medium">Title</th>
                            <th class="px-4 py-2 font-medium hidden sm:table-cell">Layout</th>
                            <th class="px-4 py-2 font-medium hidden sm:table-cell">Entries</th>
                            <th class="px-4 py-2 font-medium">Status</th>
                            <th class="px-4 py-2 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        @foreach ($sections as $section)
                            <tr class="odd:bg-white dark:odd:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="mpcp-section-row-{{ $section->slug }}">
                                <td class="px-4 py-2 text-xs text-stone-500 dark:text-stone-400">{{ $section->display_order }}</td>
                                <td class="px-4 py-2">
                                    <p class="font-medium text-stone-900 dark:text-stone-100">{{ $section->title_en }}</p>
                                    @if ($section->title_bn)
                                        <p class="text-xs text-stone-500 dark:text-stone-400">{{ $section->title_bn }}</p>
                                    @endif
                                </td>
                                <td class="px-4 py-2 hidden sm:table-cell">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700">{{ $section->layout }}</span>
                                </td>
                                <td class="px-4 py-2 hidden sm:table-cell text-stone-700 dark:text-stone-300">{{ $section->entries_count }}</td>
                                <td class="px-4 py-2">
                                    @if ($section->published)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-emerald-100 dark:bg-emerald-950 text-emerald-900 dark:text-emerald-200 border border-emerald-200 dark:border-emerald-800">Published</span>
                                    @else
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 border border-stone-200 dark:border-stone-700">Draft</span>
                                    @endif
                                </td>
                                <td class="px-4 py-2 text-right whitespace-nowrap">
                                    <a href="{{ route('admin.mpcp.entries.index', $section) }}" class="text-orange-700 hover:underline text-xs mr-3">Entries →</a>
                                    <a href="{{ route('admin.mpcp.sections.edit', $section) }}" class="text-stone-700 dark:text-stone-100 hover:underline text-xs mr-3">Edit</a>
                                    <x-confirm-form
                                        :action="route('admin.mpcp.sections.destroy', $section)"
                                        method="DELETE"
                                        title="Delete section?"
                                        body="This deletes the section AND every entry under it. Cannot be undone."
                                        confirm-label="Delete">
                                        <button type="button" class="text-rose-700 dark:text-rose-400 hover:underline text-xs" data-testid="delete-section-{{ $section->slug }}">Delete</button>
                                    </x-confirm-form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-admin-layout>
