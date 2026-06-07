<x-admin-layout title="System" active="system">
    <header class="mb-6">
        <h1 class="text-3xl font-semibold tracking-tight">System</h1>
        <p class="mt-1 text-stone-600">Scheduled jobs + queue health. Read-only schedule (defined in code), live actions on failed jobs.</p>
    </header>

    {{-- ============== Schedule ============== --}}
    <section class="mb-10" data-testid="system-schedule">
        <h2 class="text-lg font-semibold text-stone-900 mb-3">Scheduled tasks</h2>
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
            @if (count($scheduledTasks) === 0)
                <p class="px-6 py-12 text-center text-stone-500 text-sm">No scheduled tasks registered.</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Task</th>
                            <th class="px-4 py-2 font-medium hidden md:table-cell">Cron</th>
                            <th class="px-4 py-2 font-medium">Next run</th>
                            <th class="px-4 py-2 font-medium hidden lg:table-cell">TZ</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($scheduledTasks as $task)
                            <tr class="odd:bg-white even:bg-stone-50/50">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-900">{{ $task['description'] }}</p>
                                    <p class="text-xs text-stone-500 font-mono mt-0.5 break-all">{{ $task['command'] }}</p>
                                </td>
                                <td class="px-4 py-3 hidden md:table-cell">
                                    <code class="text-xs text-stone-700 bg-stone-100 px-2 py-0.5 rounded">{{ $task['expression'] }}</code>
                                </td>
                                <td class="px-4 py-3 text-stone-700 text-xs">
                                    {{ $task['next_run']->format('d M Y H:i') }}
                                    <span class="block text-stone-500">in {{ $task['next_run']->diffForHumans(now(), \Carbon\CarbonInterface::DIFF_RELATIVE_TO_NOW, true) }}</span>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-500 hidden lg:table-cell">{{ $task['timezone'] ?? config('app.timezone') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>
        <p class="mt-2 text-xs text-stone-500">Scheduler runs via the <code>scheduler</code> compose service (<code>php artisan schedule:work</code>). To pause everything, stop that container.</p>
    </section>

    {{-- ============== Queue stats ============== --}}
    <section class="mb-10" data-testid="system-queue-stats">
        <div class="flex items-center gap-3 mb-3 flex-wrap">
            <h2 class="text-lg font-semibold text-stone-900">Queue</h2>
            @if ($workerStatus['running'])
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-900 text-xs font-medium border border-emerald-200" data-testid="worker-status-running">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                    Worker running
                    @if ($workerStatus['last_seen'])
                        <span class="text-emerald-700/80">· heartbeat {{ $workerStatus['age_seconds'] }}s ago</span>
                    @endif
                </span>
            @else
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full bg-rose-100 text-rose-900 text-xs font-medium border border-rose-200" data-testid="worker-status-stopped">
                    <span class="w-1.5 h-1.5 rounded-full bg-rose-500"></span>
                    Worker not detected
                    @if ($workerStatus['last_seen'])
                        <span class="text-rose-700/80">· last heartbeat {{ $workerStatus['last_seen']->diffForHumans() }}</span>
                    @else
                        <span class="text-rose-700/80">· no heartbeat seen</span>
                    @endif
                </span>
            @endif
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
            <div class="bg-white rounded-2xl border border-stone-200 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Pending</p>
                <p class="mt-2 text-3xl font-semibold text-stone-900" data-testid="queue-pending">{{ $queueStats['pending'] }}</p>
                <p class="mt-1 text-xs text-stone-500">ready for a worker</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-200 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Running</p>
                <p class="mt-2 text-3xl font-semibold text-amber-700" data-testid="queue-reserved">{{ $queueStats['reserved'] }}</p>
                <p class="mt-1 text-xs text-stone-500">being processed</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-200 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Delayed</p>
                <p class="mt-2 text-3xl font-semibold text-stone-900" data-testid="queue-delayed">{{ $queueStats['delayed'] }}</p>
                <p class="mt-1 text-xs text-stone-500">scheduled later</p>
            </div>
            <div class="bg-white rounded-2xl border border-stone-200 p-4">
                <p class="text-xs uppercase tracking-wide text-stone-500">Failed</p>
                <p class="mt-2 text-3xl font-semibold text-rose-700" data-testid="queue-failed">{{ $failedJobs->count() }}</p>
                <p class="mt-1 text-xs text-stone-500">in failed_jobs</p>
            </div>
        </div>
        <p class="mt-2 text-xs text-stone-500">
            Driver: <code>{{ config('queue.default') }}</code>. Worker runs via the <code>queue</code> compose service.
            @if (! $workerStatus['running'])
                <span class="block mt-1 text-rose-700">Stuck? Bring the worker up with <code>sail up -d queue</code> and refresh.</span>
            @endif
        </p>
    </section>

    {{-- ============== Failed jobs ============== --}}
    <section data-testid="system-failed-jobs">
        <div class="flex items-end justify-between mb-3">
            <h2 class="text-lg font-semibold text-stone-900">Failed jobs</h2>
            @if ($failedJobs->isNotEmpty())
                <x-confirm-form
                    :action="route('admin.system.failed.flush')"
                    method="POST"
                    title="Flush all failed jobs?"
                    body="This will permanently remove every row from failed_jobs."
                    confirm-label="Flush all"
                >
                    <button type="button" class="text-xs text-rose-700 hover:underline" data-testid="flush-failed-jobs">Flush all</button>
                </x-confirm-form>
            @endif
        </div>
        <div class="bg-white rounded-2xl border border-stone-200 overflow-hidden">
            @if ($failedJobs->isEmpty())
                <p class="px-6 py-12 text-center text-stone-500 text-sm">No failed jobs. 🎉</p>
            @else
                <table class="w-full text-sm">
                    <thead class="bg-stone-50 text-stone-500 text-left">
                        <tr>
                            <th class="px-4 py-2 font-medium">Job</th>
                            <th class="px-4 py-2 font-medium hidden sm:table-cell">Queue</th>
                            <th class="px-4 py-2 font-medium hidden md:table-cell">Failed</th>
                            <th class="px-4 py-2 font-medium text-right"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-stone-100">
                        @foreach ($failedJobs as $job)
                            <tr class="odd:bg-white even:bg-stone-50/50" data-testid="failed-job-{{ $job->id }}">
                                <td class="px-4 py-3">
                                    <p class="font-medium text-stone-900 break-words">{{ $job->display_name }}</p>
                                    <p class="text-xs text-rose-700 break-words mt-0.5">{{ $job->exception_summary }}</p>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-600 hidden sm:table-cell">
                                    <code class="bg-stone-100 px-1 rounded">{{ $job->queue }}</code>
                                </td>
                                <td class="px-4 py-3 text-xs text-stone-500 hidden md:table-cell">
                                    {{ $job->failed_at->diffForHumans() }}
                                </td>
                                <td class="px-4 py-3 text-right space-x-2 whitespace-nowrap">
                                    <form method="POST" action="{{ route('admin.system.failed.retry', $job->uuid) }}" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="inline-flex items-center px-2.5 py-1 rounded-full bg-stone-900 text-amber-50 hover:bg-stone-800 text-xs"
                                                data-testid="retry-failed-{{ $job->id }}">Retry</button>
                                    </form>
                                    <x-confirm-form
                                        :action="route('admin.system.failed.forget', $job->uuid)"
                                        method="POST"
                                        title="Forget failed job?"
                                        body="This removes the row from failed_jobs without retrying."
                                        confirm-label="Forget"
                                    >
                                        <button type="button"
                                                class="inline-flex items-center px-2.5 py-1 rounded-full bg-rose-50 text-rose-900 border border-rose-200 hover:bg-rose-100 text-xs"
                                                data-testid="forget-failed-{{ $job->id }}">Forget</button>
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
