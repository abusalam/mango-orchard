<x-site-layout :title="$scheme->name.' — Aamar Malda'">
    <section class="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8 py-8">
        <a
            href="{{ route('monitoring.schemes.index') }}"
            class="inline-flex items-center gap-1 text-sm text-stone-600 dark:text-stone-300 hover:text-stone-900 dark:text-stone-100 mb-3"
            data-testid="back-to-schemes-index"
        >
            <svg class="w-3 h-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            All schemes
        </a>
        <header class="mb-6 flex items-end justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight">{{ $scheme->name }}</h1>
                <p class="mt-1 text-stone-600 dark:text-stone-300 text-sm">Owner: {{ $scheme->owner?->name }} · Status: {{ \App\Modules\SchemeMonitoring\Models\Scheme::STATUSES[$scheme->status] }}</p>
            </div>
            <a href="{{ route('monitoring.schemes.edit', $scheme) }}" class="text-sm underline">Edit</a>
        </header>
        @if ($scheme->description)<p class="mb-4 text-stone-700 dark:text-stone-300 whitespace-pre-line">{{ $scheme->description }}</p>@endif

        <h2 class="text-lg font-semibold mt-8 mb-3">Tasks</h2>
        <div class="bg-white dark:bg-stone-950 rounded-2xl border border-stone-200 dark:border-stone-800 overflow-hidden">
            @if ($scheme->tasks->isEmpty())
                <p class="px-6 py-8 text-center text-stone-500 dark:text-stone-400 text-sm">No tasks under this scheme yet.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-100 dark:bg-stone-800 text-stone-600 dark:text-stone-300 text-left"><tr><th class="px-4 py-2">Title</th><th class="px-4 py-2">Assignee</th><th class="px-4 py-2">Deadline</th><th class="px-4 py-2">Status</th></tr></thead>
                    <tbody class="divide-y divide-stone-100 dark:divide-stone-800">
                        @foreach ($scheme->tasks as $task)
                            <tr>
                                <td class="px-4 py-2">{{ $task->title }}</td>
                                <td class="px-4 py-2 text-stone-600 dark:text-stone-300">{{ $task->assignee?->name ?? '—' }}</td>
                                <td class="px-4 py-2 text-stone-600 dark:text-stone-300">{{ $task->deadline->format('d M Y') }}</td>
                                <td class="px-4 py-2">{{ \App\Modules\SchemeMonitoring\Models\Task::STATUSES[$task->status] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    </section>
</x-site-layout>
