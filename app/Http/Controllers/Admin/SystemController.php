<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Permissions;
use Carbon\Carbon;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Sysadmin dashboard for the scheduler and queue subsystems. Surfaces:
 *   - Every registered scheduled task, its cron expression, when it last
 *     ran (best-effort from cache), and when it'll fire next.
 *   - Live counts of pending + reserved + delayed queue rows from the
 *     `jobs` table, plus the failed-job log.
 *   - Per-row retry / delete actions on failed jobs (calls through to
 *     `queue:retry` so the worker picks the row back up), plus a
 *     "flush all" for a clean slate.
 *
 * Scoped to `settings.manage` since this is sysadmin territory.
 */
class SystemController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::SETTINGS_MANAGE]),
        ];
    }

    public function index(Schedule $schedule): View
    {
        return view('admin.system.index', [
            'scheduledTasks' => $this->scheduledTasks($schedule),
            'queueStats' => $this->queueStats(),
            'failedJobs' => $this->failedJobs(),
            'workerStatus' => $this->workerStatus(),
        ]);
    }

    /**
     * Read the queue-worker heartbeat written by the Looping listener in
     * AppServiceProvider. A heartbeat younger than 15s means the worker
     * is alive (default poll cycle is 3s; 15s leaves slack for slow
     * jobs). Older or missing → the worker is stopped.
     *
     * @return array{running: bool, last_seen: ?\Carbon\Carbon, age_seconds: ?int}
     */
    private function workerStatus(): array
    {
        $heartbeat = Cache::get('queue:worker:heartbeat');
        if ($heartbeat === null) {
            return ['running' => false, 'last_seen' => null, 'age_seconds' => null];
        }

        $lastSeen = Carbon::createFromTimestamp((int) $heartbeat);
        $age = (int) $lastSeen->diffInSeconds(now());

        return [
            'running' => $age <= 15,
            'last_seen' => $lastSeen,
            'age_seconds' => $age,
        ];
    }

    /**
     * Re-queue a single failed job. Wraps `queue:retry` so the same
     * code-path runs as if you'd typed it on the shell — the job goes
     * back onto its original connection / queue.
     */
    public function retryFailedJob(string $id): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => [$id]]);

        return back()->with('status', 'Failed job queued for retry.');
    }

    /**
     * Forget a single failed job permanently.
     */
    public function forgetFailedJob(string $id): RedirectResponse
    {
        Artisan::call('queue:forget', ['id' => $id]);

        return back()->with('status', 'Failed job removed.');
    }

    /**
     * Wipe the entire failed-job log. Useful after triaging.
     */
    public function flushFailedJobs(): RedirectResponse
    {
        Artisan::call('queue:flush');

        return back()->with('status', 'All failed jobs flushed.');
    }

    /**
     * Build a row per registered scheduled task with the bits an admin
     * actually wants to see at a glance.
     *
     * @return list<array{description: string, command: string, expression: string, next_run: ?Carbon, timezone: ?string}>
     */
    private function scheduledTasks(Schedule $schedule): array
    {
        return collect($schedule->events())
            ->map(function (Event $event) {
                $command = $this->describeCommand($event);

                return [
                    'description' => $event->description ?: $command,
                    'command' => $command,
                    'expression' => $event->getExpression(),
                    // nextRunDate is a DateTimeImmutable in framework-land;
                    // wrap as a Carbon so the view can format consistently.
                    'next_run' => Carbon::instance($event->nextRunDate()),
                    'timezone' => $event->timezone,
                ];
            })
            ->all();
    }

    /**
     * Extract a human-friendly command label. Closures, invokable jobs,
     * and shell calls all flow through this so the table never shows
     * "Closure::__invoke" cryptically.
     */
    private function describeCommand(Event $event): string
    {
        if (! empty($event->command)) {
            // Strip the surrounding `php artisan` prefix that the
            // framework adds for artisan-bound events.
            return trim(preg_replace("/^'?".preg_quote(PHP_BINARY, '/').".+?artisan'?\\s+/", '', $event->command));
        }

        return $event->description ?: 'Closure / inline callback';
    }

    /**
     * Aggregates over the `jobs` table grouped by the worker-meaningful
     * states. `reserved_at` IS NOT NULL → being processed right now;
     * `available_at > now` → delayed.
     *
     * @return array{pending: int, reserved: int, delayed: int, total: int}
     */
    private function queueStats(): array
    {
        $now = now()->timestamp;

        $rows = DB::table('jobs')
            ->selectRaw('COUNT(*) AS total')
            ->selectRaw("COUNT(*) FILTER (WHERE reserved_at IS NOT NULL) AS reserved")
            ->selectRaw("COUNT(*) FILTER (WHERE reserved_at IS NULL AND available_at > ?) AS delayed", [$now])
            ->selectRaw("COUNT(*) FILTER (WHERE reserved_at IS NULL AND available_at <= ?) AS pending", [$now])
            ->first();

        return [
            'pending' => (int) ($rows->pending ?? 0),
            'reserved' => (int) ($rows->reserved ?? 0),
            'delayed' => (int) ($rows->delayed ?? 0),
            'total' => (int) ($rows->total ?? 0),
        ];
    }

    /**
     * Failed jobs newest-first with a parsed exception summary.
     * Exception text is truncated for the table; the full payload stays
     * one click away on the row itself.
     *
     * @return \Illuminate\Support\Collection<int, object>
     */
    private function failedJobs(): \Illuminate\Support\Collection
    {
        return DB::table('failed_jobs')
            ->orderByDesc('failed_at')
            ->limit(50)
            ->get()
            ->map(function ($row) {
                $payload = json_decode($row->payload ?? '{}', true) ?: [];
                $exception = (string) ($row->exception ?? '');
                $firstLine = strtok($exception, "\n") ?: 'Unknown error';

                return (object) [
                    'id' => $row->id,
                    'uuid' => $row->uuid,
                    'queue' => $row->queue,
                    'connection' => $row->connection,
                    'failed_at' => Carbon::parse($row->failed_at),
                    'display_name' => $payload['displayName'] ?? 'Unknown job',
                    'exception_summary' => mb_substr($firstLine, 0, 200),
                ];
            });
    }
}
