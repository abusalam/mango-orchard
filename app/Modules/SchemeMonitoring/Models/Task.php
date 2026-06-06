<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Task extends Model
{
    use HasFactory;

    public const string STATUS_PENDING = 'pending';

    public const string STATUS_IN_PROGRESS = 'in_progress';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_CANCELLED = 'cancelled';

    public const array STATUSES = [
        self::STATUS_PENDING => 'Pending',
        self::STATUS_IN_PROGRESS => 'In progress',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_CANCELLED => 'Cancelled',
    ];

    public const array OPEN_STATUSES = [self::STATUS_PENDING, self::STATUS_IN_PROGRESS];

    public const string PRIORITY_LOW = 'low';

    public const string PRIORITY_NORMAL = 'normal';

    public const string PRIORITY_HIGH = 'high';

    public const string PRIORITY_URGENT = 'urgent';

    public const array PRIORITIES = [
        self::PRIORITY_LOW => 'Low',
        self::PRIORITY_NORMAL => 'Normal',
        self::PRIORITY_HIGH => 'High',
        self::PRIORITY_URGENT => 'Urgent',
    ];

    protected $table = 'monitoring_tasks';

    protected $fillable = [
        'scheme_id', 'title', 'description', 'deadline', 'status', 'priority',
        'assigned_to', 'created_by', 'completed_at', 'last_overdue_reminder_at',
    ];

    protected $casts = [
        'deadline' => 'date',
        'completed_at' => 'datetime',
        'last_overdue_reminder_at' => 'datetime',
    ];

    protected static function newFactory(): \Database\Factories\SchemeMonitoring\TaskFactory
    {
        return \Database\Factories\SchemeMonitoring\TaskFactory::new();
    }

    /**
     * Polymorphic attachments aren't FK-cascadable in MySQL/Postgres
     * (morphs() is just two columns), so we wipe them in app code when
     * the parent goes away. Triggers each Attachment's `deleting` hook
     * so the blob on disk is removed too.
     */
    protected static function booted(): void
    {
        static::deleting(function (Task $task): void {
            $task->attachments()->get()->each->delete();
        });
    }

    public function scheme(): BelongsTo
    {
        return $this->belongsTo(Scheme::class);
    }

    public function attachments(): \Illuminate\Database\Eloquent\Relations\MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderByDesc('created_at');
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN_STATUSES, true);
    }

    public function isOverdue(): bool
    {
        return $this->isOpen() && $this->deadline->isPast();
    }

    /**
     * Tasks still requiring action — used by the dashboard and the
     * deadline-reminder command.
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', self::OPEN_STATUSES);
    }

    /**
     * Filter tasks to those visible to a given user under the hierarchy
     * scoping rules: own assignments + entire subtree underneath them.
     * `manage`-permission holders see everything (no filter applied).
     *
     * @param  array<int>  $visibleUserIds  user IDs the viewer can see (self + descendants)
     */
    public function scopeVisibleTo(Builder $query, array $visibleUserIds): Builder
    {
        return $query->whereIn('assigned_to', $visibleUserIds);
    }
}
