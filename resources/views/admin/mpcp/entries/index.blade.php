<x-admin-layout :title="'MPCP — '.$section->title_en.' entries'" active="mpcp">
    <a href="{{ route('admin.mpcp.index') }}" class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:hover:text-stone-100 mb-3">
        <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/>
        </svg>
        Sections
    </a>

    <header class="mb-6 flex items-end justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">{{ $section->title_en }}</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">{{ $entries->total() }} entries · layout: <code>{{ $section->layout }}</code></p>
        </div>
        <div class="flex items-end gap-2">
            <a href="{{ route('admin.mpcp.sections.edit', $section) }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-white dark:bg-stone-900 border border-stone-300 dark:border-stone-700 text-stone-800 dark:text-stone-100 hover:border-stone-400 text-sm">Section settings</a>
            <a href="{{ route('admin.mpcp.entries.create', $section) }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm" data-testid="new-entry">New entry</a>
        </div>
    </header>

    @if (session('status'))
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-950 dark:border-emerald-800 p-3 text-sm text-emerald-900 dark:text-emerald-100">{{ session('status') }}</div>
    @endif

    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
        @if ($entries->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No entries yet.</p>
        @else
            <div>
                {{-- The column set is admin-defined (dynamic), so it can't be
                     hand-tuned per breakpoint: below lg only the FIRST column
                     (the entry's de-facto title) renders, with the remaining
                     values folded beneath it as label: value lines. lg+ shows
                     the full dynamic table. No horizontal scrolling. --}}
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                        <tr>
                            <th class="px-3 py-2 font-medium w-12 hidden sm:table-cell">#</th>
                            @foreach ($section->columns as $col)
                                <th @class(['px-3 py-2 font-medium', 'hidden lg:table-cell' => ! $loop->first])>{{ $col['label_en'] }}</th>
                            @endforeach
                            <th class="px-3 py-2 font-medium text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        @foreach ($entries as $entry)
                            <tr class="odd:bg-white dark:odd:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="mpcp-entry-row-{{ $entry->id }}">
                                <td class="px-3 py-2 text-xs text-stone-500 dark:text-stone-400 hidden sm:table-cell">{{ $entry->position }}</td>
                                @foreach ($section->columns as $col)
                                    @php $value = $entry->data[$col['key']] ?? ''; @endphp
                                    <td @class(['px-3 py-2 text-stone-800 dark:text-stone-200 align-top max-w-md', 'hidden lg:table-cell' => ! $loop->first])>
                                        @if ($value === '')
                                            <span class="text-stone-400">—</span>
                                        @else
                                            <span class="line-clamp-2">{{ Illuminate\Support\Str::limit($value, 120) }}</span>
                                        @endif
                                        @if ($loop->first)
                                            {{-- Folded-in remaining columns while hidden below lg --}}
                                            <dl class="lg:hidden mt-1 space-y-0.5 text-xs font-normal text-stone-500 dark:text-stone-400">
                                                @foreach ($section->columns as $sub)
                                                    @php $subValue = $entry->data[$sub['key']] ?? ''; @endphp
                                                    @if ($sub['key'] !== $col['key'] && $subValue !== '')
                                                        <div><span class="text-stone-400">{{ $sub['label_en'] }}:</span> {{ Illuminate\Support\Str::limit($subValue, 80) }}</div>
                                                    @endif
                                                @endforeach
                                            </dl>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-3 py-2 text-right whitespace-nowrap align-top">
                                    <a href="{{ route('admin.mpcp.entries.edit', [$section, $entry]) }}" class="text-stone-700 dark:text-stone-100 hover:underline text-xs mr-3">Edit</a>
                                    <x-confirm-form
                                        :action="route('admin.mpcp.entries.destroy', [$section, $entry])"
                                        method="DELETE"
                                        title="Delete entry?"
                                        body="Removes this row from the section. Cannot be undone."
                                        confirm-label="Delete">
                                        <button type="button" class="text-rose-700 dark:text-rose-400 hover:underline text-xs">Delete</button>
                                    </x-confirm-form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="px-4 py-3 border-t border-stone-100 dark:border-stone-800">
                {{ $entries->withQueryString()->links() }}
            </div>
        @endif
    </div>
</x-admin-layout>
