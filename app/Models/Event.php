<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\EventTelemetryObserver;
use Database\Factories\EventFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

#[ObservedBy([EventTelemetryObserver::class])]
class Event extends Model
{
    /** @use HasFactory<EventFactory> */
    use HasFactory;

    public const string STATUS_DRAFT = 'draft';

    public const string STATUS_PUBLISHED = 'published';

    public const string STATUS_CANCELLED = 'cancelled';

    public const string STATUS_COMPLETED = 'completed';

    public const array STATUSES = [
        self::STATUS_DRAFT => 'Draft (only event managers can see this)',
        self::STATUS_PUBLISHED => 'Published (visible to everyone)',
        self::STATUS_CANCELLED => 'Cancelled (visible, marked off)',
        self::STATUS_COMPLETED => 'Completed (kept for the record)',
    ];

    protected $fillable = [
        'title',
        'slug',
        'description',
        'start_at',
        'end_at',
        'location',
        'location_url',
        'host',
        'capacity',
        'registration_url',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'capacity' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $event): void {
            if (blank($event->slug) && filled($event->title)) {
                $event->slug = self::uniqueSlugFor($event->title, $event->id);
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /** Scope to events visible to the public (everything except drafts). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [
            self::STATUS_PUBLISHED,
            self::STATUS_CANCELLED,
            self::STATUS_COMPLETED,
        ]);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        return $query->where('start_at', '>=', now())->orderBy('start_at');
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->where('start_at', '<', now())->orderByDesc('start_at');
    }

    public function isPast(): bool
    {
        return $this->start_at !== null && $this->start_at->isPast();
    }

    private static function uniqueSlugFor(string $title, ?int $ignoreId): string
    {
        $base = Str::slug($title) ?: 'event';
        $slug = $base;
        $i = 2;

        while (
            self::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($q) => $q->where('id', '!=', $ignoreId))
                ->exists()
        ) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
