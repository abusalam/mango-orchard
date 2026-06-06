<x-site-layout :title="$scheme->name.' — Aamar Malda'">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ $scheme->name }}</h1>
                <p class="mt-1 text-stone-600 text-sm">Owner: {{ $scheme->owner?->name }} · Status: {{ \App\Modules\SchemeMonitoring\Models\Scheme::STATUSES[$scheme->status] }}</p>
            </div>
            <a href="{{ route('monitoring.schemes.edit', $scheme) }}" class="text-sm underline">Edit</a>
        </header>
        @if ($scheme->description)<p class="mb-4 text-stone-700 whitespace-pre-line">{{ $scheme->description }}</p>@endif

        <h2 class="text-lg font-semibold mt-8 mb-3">Tasks</h2>
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
            @if ($scheme->tasks->isEmpty())
                <p class="px-6 py-8 text-center text-stone-500 text-sm">No tasks under this scheme yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left"><tr><th class="px-4 py-2">Title</th><th class="px-4 py-2">Assignee</th><th class="px-4 py-2">Deadline</th><th class="px-4 py-2">Status</th></tr></thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($scheme->tasks as $task)
                            <tr>
                                <td class="px-4 py-2">{{ $task->title }}</td>
                                <td class="px-4 py-2 text-stone-600">{{ $task->assignee?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-stone-600">{{ $task->deadline->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ \App\Modules\SchemeMonitoring\Models\Task::STATUSES[$task->status] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-site-layout>
