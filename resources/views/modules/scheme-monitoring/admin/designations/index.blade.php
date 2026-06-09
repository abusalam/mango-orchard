<x-admin-layout title="Designations" active="monitoring-designations">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Designations</h1>
            <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Role labels tagged to users in the monitoring hierarchy.</p>
        </div>
        <a href="{{ route('admin.monitoring.designations.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">New designation</a>
    </header>
    <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden" data-testid="designations-table">
        @if ($designations->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 dark:text-stone-400 text-sm">No designations yet.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left">
                    <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Level</th><th class="px-4 py-2">Reports to</th><th class="px-4 py-2">Description</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                    @foreach ($designations as $d)
                        <tr class="odd:bg-white dark:odd:bg-stone-950 dark:bg-stone-950 even:bg-stone-50/50 dark:even:bg-stone-900" data-testid="designation-row-{{ $d->id }}">
                            <td class="px-4 py-2 font-medium">{{ $d->name }}</td>
                            <td class="px-4 py-2 text-stone-600 dark:text-stone-300">{{ $d->level }}</td>
                            <td class="px-4 py-2 text-stone-600 dark:text-stone-300">
                                @if ($d->parent)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-stone-100 dark:bg-stone-800 text-stone-700 dark:text-stone-300 text-xs">{{ $d->parent->name }}</span>
                                @else
                                    <span class="text-stone-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-stone-600 dark:text-stone-300">{{ $d->description }}</td>
                            <td class="px-4 py-2 text-right text-xs space-x-3">
                                <a href="{{ route('admin.monitoring.designations.edit', $d) }}" class="text-stone-700 dark:text-stone-100 hover:underline">Edit</a>
                                <x-confirm-form
                                    :action="route('admin.monitoring.designations.destroy', $d)"
                                    method="DELETE"
                                    :title="'Delete '.$d->name.'?'"
                                    message="Users tagged with this designation will lose it. Their reporting parents (anyone holding a parent designation) will also stop seeing them in their subtree."
                                    confirm-label="Delete designation"
                                >
                                    <button type="button" class="text-rose-700 dark:text-rose-400 hover:underline">Delete</button>
                                </x-confirm-form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $designations->links() }}</div>
</x-admin-layout>
