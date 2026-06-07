<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use App\Modules\SchemeMonitoring\Notifications\TaskNotificationRecipients;
use App\Modules\SchemeMonitoring\Notifications\TaskStatusChanged;
use App\Modules\SchemeMonitoring\Notifications\TaskUpdated;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\View\View;

class TaskController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_VIEW])];
    }

    public function index(Request $request, Hierarchy $hierarchy): View
    {
        $viewer = $request->user();
        $canManage = $viewer->can(Permissions::MONITORING_MANAGE);
        $visibleUserIds = $canManage ? null : $hierarchy->descendantUserIds($viewer->id);

        $search = trim((string) $request->query('q', ''));

        $tasks = Task::query()
            ->with(['scheme', 'assignee'])
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('assigned_to', $visibleUserIds))
            // Free-text search across the task itself, plus the parent
            // scheme so "DWP" or "Drinking Water" hit the right tasks
            // even if the user only remembers the project name.
            ->when($search !== '', function ($q) use ($search): void {
                $needle = '%'.$search.'%';
                $q->where(function ($q) use ($needle): void {
                    $q->where('title', 'ilike', $needle)
                        ->orWhere('description', 'ilike', $needle)
                        ->orWhereHas('scheme', fn ($q) => $q
                            ->where('name', 'ilike', $needle)
                            ->orWhere('abbreviation', 'ilike', $needle));
                });
            })
            ->orderBy('deadline')
            ->paginate(20)
            ->withQueryString();

        return view('scheme-monitoring::tasks.index', [
            'tasks' => $tasks,
            'search' => $search,
        ]);
    }

    public function create(Request $request, Hierarchy $hierarchy): View
    {
        Gate::authorize('create', Task::class);

        $viewer = $request->user();
        $visibleUserIds = $hierarchy->descendantUserIds($viewer->id);

        return view('scheme-monitoring::tasks.create', [
            'task' => new Task(),
            'schemes' => Scheme::query()
                ->when(! $viewer->can(Permissions::MONITORING_MANAGE),
                    fn ($q) => $q->whereIn('owner_id', $visibleUserIds))
                ->orderBy('name')->get(),
            'assignableUserIds' => $visibleUserIds,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Task::class);

        $data = $this->validated($request);

        $task = Task::create([
            ...$data,
            'created_by' => $request->user()->id,
        ]);

        // Redirect to the edit page so the creator can immediately add
        // attachments, tweak the deadline, etc. — the index/dashboard
        // doesn't expose those follow-ups.
        return redirect()
            ->route('monitoring.tasks.edit', $task)
            ->with('status', 'Task created.');
    }

    public function edit(Task $task, Request $request, Hierarchy $hierarchy): View
    {
        Gate::authorize('update', $task);

        $visibleUserIds = $hierarchy->descendantUserIds($request->user()->id);

        return view('scheme-monitoring::tasks.edit', [
            'task' => $task,
            'schemes' => Scheme::query()->orderBy('name')->get(),
            'assignableUserIds' => $visibleUserIds,
        ]);
    }

    public function update(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('update', $task);

        $data = $this->validated($request);

        if ($data['status'] === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = now();
        }
        if ($data['status'] !== Task::STATUS_COMPLETED) {
            $data['completed_at'] = null;
        }

        // Snapshot the pre-update state so we can build a "what changed"
        // diff for the notification AND detect a status-only edit. The
        // assignee snapshot also tells us if the task was reassigned —
        // both the old and the new assignee get a heads-up.
        $previousAssigneeId = (int) $task->assigned_to;
        $previousStatus = $task->status;
        $changes = $this->diff($task, $data);

        $task->update($data);

        // Dispatch if EITHER fields changed (TaskUpdated) OR the status
        // flipped (TaskStatusChanged) — a status-only PUT still needs to
        // notify even though there's no field diff.
        if ($changes !== [] || $previousStatus !== $task->status) {
            $this->dispatchUpdateNotifications($task->fresh(), $changes, $previousStatus, $previousAssigneeId, $request->user());
        }

        return redirect()
            ->route('monitoring.dashboard')
            ->with('status', 'Task updated.');
    }

    public function updateStatus(Request $request, Task $task): RedirectResponse
    {
        Gate::authorize('updateStatus', $task);

        $data = $request->validate([
            'status' => ['required', 'in:'.implode(',', array_keys(Task::STATUSES))],
        ]);

        $patch = ['status' => $data['status']];
        if ($data['status'] === Task::STATUS_COMPLETED && $task->status !== Task::STATUS_COMPLETED) {
            $patch['completed_at'] = now();
        } elseif ($data['status'] !== Task::STATUS_COMPLETED) {
            $patch['completed_at'] = null;
        }

        $previousStatus = $task->status;
        $task->update($patch);

        if ($previousStatus !== $patch['status']) {
            $recipients = TaskNotificationRecipients::for($task->fresh(), $request->user());
            if ($recipients->isNotEmpty()) {
                Notification::send(
                    $recipients,
                    new TaskStatusChanged($task->fresh(), $previousStatus, $patch['status'], $request->user()),
                );
            }
        }

        return back()->with('status', 'Task status updated.');
    }

    public function destroy(Task $task): RedirectResponse
    {
        Gate::authorize('delete', $task);

        $task->delete();

        return redirect()
            ->route('monitoring.dashboard')
            ->with('status', 'Task deleted.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'scheme_id' => ['required', 'integer', 'exists:monitoring_schemes,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'deadline' => ['required', 'date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Task::STATUSES))],
            'priority' => ['required', 'in:'.implode(',', array_keys(Task::PRIORITIES))],
            'assigned_to' => ['required', 'integer', 'exists:users,id'],
        ]);
    }

    /**
     * Compute a per-field diff between the task's current state and the
     * validated payload. Only fields the notification cares about are
     * compared — completed_at + status changes are surfaced elsewhere.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, array{from: mixed, to: mixed}>
     */
    private function diff(Task $task, array $data): array
    {
        $tracked = ['title', 'description', 'deadline', 'priority', 'assigned_to', 'scheme_id'];
        $changes = [];
        foreach ($tracked as $field) {
            if (! array_key_exists($field, $data)) {
                continue;
            }
            $current = $field === 'deadline'
                ? $task->deadline?->toDateString()
                : $task->{$field};
            $next = $data[$field];
            if ((string) $current !== (string) $next) {
                $changes[$field] = ['from' => $current, 'to' => $next];
            }
        }

        return $changes;
    }

    /**
     * Send the right notification(s) for an edit. A status flip inside a
     * broader edit emits BOTH the TaskStatusChanged email and the
     * TaskUpdated email — the former carries the state transition, the
     * latter the field diff.
     *
     * @param  array<string, array{from: mixed, to: mixed}>  $changes
     */
    private function dispatchUpdateNotifications(Task $task, array $changes, string $previousStatus, int $previousAssigneeId, ?\App\Models\User $actor): void
    {
        $recipients = TaskNotificationRecipients::for(
            $task,
            $actor,
            $previousAssigneeId !== (int) $task->assigned_to ? $previousAssigneeId : null,
        );

        if ($recipients->isEmpty()) {
            return;
        }

        // Strip status from the field-diff notification — TaskStatusChanged
        // carries that transition in a richer way.
        $fieldChanges = $changes;
        unset($fieldChanges['status']);
        if ($fieldChanges !== []) {
            Notification::send($recipients, new TaskUpdated($task, $fieldChanges, $actor));
        }

        if ($previousStatus !== $task->status) {
            Notification::send($recipients, new TaskStatusChanged($task, $previousStatus, $task->status, $actor));
        }
    }
}
