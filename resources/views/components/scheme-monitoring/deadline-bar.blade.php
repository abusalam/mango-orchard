@props(['task'])

@php
    use App\Modules\SchemeMonitoring\Models\Task;

    $now = now()->startOfDay();
    $deadlineDay = $task->deadline->copy()->startOfDay();

    // Anchor the bar to the task's own creation date — that's THIS task's
    // runway. Anchoring to scheme.start_date was misleading for tasks
    // created mid-scheme: a yearly programme starting Apr 1 makes every
    // mid-year task look mostly-elapsed before any real work is due.
    // The scheme-level timeline is surfaced separately by the duration
    // chip on the dashboard card.
    $startDay = $task->created_at->copy()->startOfDay();

    // Runway: start → deadline, in days. Floor of 1 so the progress math
    // doesn't divide by zero for same-day windows.
    $totalDays = max(1, $startDay->diffInDays($deadlineDay));
    // Signed elapsed: if the anchor is in the future (scheme not started
    // yet) we want 0, not a confusing absolute-value figure.
    $elapsedDays = max(0, (int) round($startDay->diffInDays($now, false)));
    $remainingDays = (int) $now->diffInDays($deadlineDay, false);

    // Width tracks the % of runway burned. 0% at creation, fills left → right
    // as the deadline nears, naturally clamps at 100% for open-but-overdue.
    $progressPct = (int) round(min(100, max(0, ($elapsedDays / $totalDays) * 100)));

    // Five-step urgency bucket that combines BOTH proportional progress
    // (elapsedPct) and absolute days-remaining — whichever escalates first
    // wins. That way a 60-day task hits "warming" the day it crosses 50%
    // even if 30 days remain on the clock, AND a 3-day rush hits "urgent"
    // the day it has 3 days left even if elapsedPct is only 0%.
    if ($task->status === Task::STATUS_COMPLETED) {
        $bucket = 'completed';
    } elseif ($task->status === Task::STATUS_CANCELLED) {
        $bucket = 'cancelled';
    } elseif ($remainingDays < 0) {
        $bucket = 'overdue';
    } elseif ($remainingDays === 0) {
        $bucket = 'due-today';
    } elseif ($progressPct >= 90 || $remainingDays <= 1) {
        $bucket = 'critical';
    } elseif ($progressPct >= 75 || $remainingDays <= 3) {
        $bucket = 'urgent';
    } elseif ($progressPct >= 50 || $remainingDays <= 7) {
        $bucket = 'warming';
    } elseif ($progressPct >= 25) {
        $bucket = 'on-track';
    } else {
        $bucket = 'early';
    }

    // Neutral track so the empty portion stays clearly grey — colour ramp
    // moves through the standard "go → caution → stop" spectrum:
    //   green → lime → amber → orange → red
    $track = 'bg-stone-200';

    $config = [
        'early' => ['fill' => 'bg-emerald-500', 'label' => $remainingDays.'d left', 'labelClass' => 'text-emerald-800'],
        'on-track' => ['fill' => 'bg-lime-500', 'label' => $remainingDays.'d left', 'labelClass' => 'text-lime-800'],
        'warming' => ['fill' => 'bg-amber-400', 'label' => $remainingDays.'d left', 'labelClass' => 'text-amber-800'],
        'urgent' => ['fill' => 'bg-orange-500', 'label' => $remainingDays.'d left', 'labelClass' => 'text-orange-800'],
        'critical' => ['fill' => 'bg-red-500', 'label' => $remainingDays.'d left', 'labelClass' => 'text-red-800'],
        'due-today' => ['fill' => 'bg-rose-600', 'label' => 'Due today', 'labelClass' => 'text-rose-800'],
        'overdue' => ['fill' => 'bg-rose-700', 'label' => 'Overdue '.abs($remainingDays).'d', 'labelClass' => 'text-rose-800'],
        'completed' => ['fill' => 'bg-emerald-600', 'label' => 'Completed', 'labelClass' => 'text-emerald-800'],
        'cancelled' => ['fill' => 'bg-stone-400', 'label' => 'Cancelled', 'labelClass' => 'text-stone-600'],
    ][$bucket];

    // Open tasks (including overdue/due-today) show real progress; the only
    // bucket we snap to 100% is `completed` because the task is done.
    $renderedWidth = $bucket === 'completed' ? 100 : $progressPct;
@endphp

<div class="w-full" data-testid="deadline-bar" data-bucket="{{ $bucket }}" data-progress="{{ $progressPct }}">
    {{-- 3-column header: start date (left) · status/remaining (center) · due date (right).
         Grid (not flex justify-between) so the middle slot is genuinely centered
         in the bar regardless of how short / long the side labels are. --}}
    <div class="grid grid-cols-3 items-center text-xs mb-1.5 leading-tight">
        <span class="text-stone-500 text-left" data-testid="deadline-bar-start">Start {{ $startDay->format('d M') }}</span>
        <span class="font-semibold text-center {{ $config['labelClass'] }}" data-testid="deadline-bar-label">{{ $config['label'] }}</span>
        <span class="text-stone-500 text-right" data-testid="deadline-bar-due">Due {{ $task->deadline->format('d M') }}</span>
    </div>
    <div
        class="relative h-3 w-full rounded-full overflow-hidden {{ $track }}"
        title="Start {{ $startDay->format('d M Y') }} · Deadline {{ $deadlineDay->format('d M Y') }} · {{ $progressPct }}% of runway used"
    >
        {{-- Fill grows from the left edge as time passes. Inline width is
             percent-based so it scales with whatever the parent gives us. --}}
        <div
            class="absolute inset-y-0 left-0 {{ $config['fill'] }} transition-[width] duration-500 ease-out"
            style="width: {{ $renderedWidth }}%"
        ></div>
    </div>
</div>
