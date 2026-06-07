<x-admin-layout title="Designations" active="monitoring-designations">
    <header class="mb-6 flex items-end justify-between">
        <div>
            <h1 class="text-3xl font-semibold tracking-tight">Designations</h1>
            <p class="mt-1 text-stone-600 text-sm">Role labels tagged to users in the monitoring hierarchy.</p>
        </div>
        <a href="{{ route('admin.monitoring.designations.create') }}" class="inline-flex items-center px-3 py-1.5 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-sm">New designation</a>
    </header>
    <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden" data-testid="designations-table">
        @if ($designations->isEmpty())
            <p class="px-6 py-12 text-center text-stone-500 text-sm">No designations yet.</p>
        @else
            <table class="w-full text-sm">
                <thead class="bg-stone-50 text-stone-500 text-left">
                    <tr><th class="px-4 py-2">Name</th><th class="px-4 py-2">Level</th><th class="px-4 py-2">Reports to</th><th class="px-4 py-2">Description</th><th></th></tr>
                </thead>
                <tbody class="divide-y divide-stone-100">
                    @foreach ($designations as $d)
                        <tr class="odd:bg-white even:bg-stone-50/50" data-testid="designation-row-{{ $d->id }}">
                            <td class="px-4 py-2 font-medium">{{ $d->name }}</td>
                            <td class="px-4 py-2 text-stone-600">{{ $d->level }}</td>
                            <td class="px-4 py-2 text-stone-600">
                                @if ($d->parent)
                                    <span class="inline-flex px-2 py-0.5 rounded-full bg-stone-100 text-stone-700 text-xs">{{ $d->parent->name }}</span>
                                @else
                                    <span class="text-stone-400 text-xs">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-2 text-stone-600">{{ $d->description }}</td>
                            <td class="px-4 py-2 text-right text-xs space-x-3">
                                <a href="{{ route('admin.monitoring.designations.edit', $d) }}" class="text-stone-600 hover:text-stone-900">Edit</a>
                                <form method="POST" action="{{ route('admin.monitoring.designations.destroy', $d) }}" class="inline">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-rose-700 hover:underline">Delete</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div class="mt-4">{{ $designations->links() }}</div>
</x-admin-layout>
