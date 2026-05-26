<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\AdvisoryTelemetryObserver;
use Database\Factories\AdvisoryFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[ObservedBy([AdvisoryTelemetryObserver::class])]
class Advisory extends Model
{
    /** @use HasFactory<AdvisoryFactory> */
    use HasFactory;

    public const string CATEGORY_SEASONAL = 'seasonal';
    public const string CATEGORY_BEST_PRACTICE = 'best_practice';
    public const string CATEGORY_PEST_ALERT = 'pest_alert';

    public const array CATEGORIES = [
        self::CATEGORY_SEASONAL => 'Seasonal',
        self::CATEGORY_BEST_PRACTICE => 'Best practice',
        self::CATEGORY_PEST_ALERT => 'Pest alert',
    ];

    public const string SEVERITY_INFO = 'info';
    public const string SEVERITY_WARNING = 'warning';
    public const string SEVERITY_URGENT = 'urgent';

    public const array SEVERITIES = [
        self::SEVERITY_INFO => 'Info',
        self::SEVERITY_WARNING => 'Warning',
        self::SEVERITY_URGENT => 'Urgent',
    ];

    /** Severity rank — bigger = more important. Used for dashboard ordering. */
    public const array SEVERITY_RANK = [
        self::SEVERITY_INFO => 1,
        self::SEVERITY_WARNING => 2,
        self::SEVERITY_URGENT => 3,
    ];

    protected $fillable = [
        'title',
        'body',
        'category',
        'severity',
        'issued_by',
        'issued_at',
        'expires_at',
        'published',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'published' => 'boolean',
    ];

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }

    public function varieties(): BelongsToMany
    {
        return $this->belongsToMany(MangoVariety::class, 'advisory_variety');
    }

    /**
     * Published, issued, and not yet expired — ie. visible to the public
     * and currently in force.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->where('published', true)
            ->where(function (Builder $q) {
                $q->whereNull('issued_at')->orWhere('issued_at', '<=', now());
            })
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>', now());
            });
    }

    /** True if no variety pivot rows — applies to every variety. */
    public function isGeneral(): bool
    {
        return $this->varieties->isEmpty();
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }
}
