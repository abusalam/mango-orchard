<?php

declare(strict_types=1);

namespace App\Models;

use App\Observers\ListingTelemetryObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([ListingTelemetryObserver::class])]
class Listing extends Model
{
    /** @use HasFactory<\Database\Factories\ListingFactory> */
    use HasFactory;

    public const string STATUS_DRAFT = 'draft';
    public const string STATUS_PUBLISHED = 'published';
    public const string STATUS_SOLD_OUT = 'sold_out';

    public const array STATUSES = [
        self::STATUS_DRAFT => 'Draft (only you can see this)',
        self::STATUS_PUBLISHED => 'Published (visible in the marketplace)',
        self::STATUS_SOLD_OUT => 'Sold out (visible, marked unavailable)',
    ];

    protected $fillable = [
        'user_id',
        'mango_variety_id',
        'farm_name',
        'location',
        'description',
        'availability_start_month',
        'availability_end_month',
        'price_per_kg',
        'quantity_available_kg',
        'contact_email',
        'contact_phone',
        'status',
    ];

    protected $casts = [
        'availability_start_month' => 'integer',
        'availability_end_month' => 'integer',
        'price_per_kg' => 'decimal:2',
        'quantity_available_kg' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function variety(): BelongsTo
    {
        return $this->belongsTo(MangoVariety::class, 'mango_variety_id');
    }

    /** Scope to listings visible to the public (anything not in draft). */
    public function scopeVisible(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_PUBLISHED, self::STATUS_SOLD_OUT]);
    }
}
