<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Database\Factories\SchemeMonitoring\SchemeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Scheme extends Model
{
    use HasFactory;

    public const string STATUS_ACTIVE = 'active';

    public const string STATUS_PAUSED = 'paused';

    public const string STATUS_COMPLETED = 'completed';

    public const string STATUS_ARCHIVED = 'archived';

    public const array STATUSES = [
        self::STATUS_ACTIVE => 'Active',
        self::STATUS_PAUSED => 'Paused',
        self::STATUS_COMPLETED => 'Completed',
        self::STATUS_ARCHIVED => 'Archived',
    ];

    protected $table = 'monitoring_schemes';

    protected $fillable = [
        'name', 'abbreviation', 'description', 'start_date', 'end_date', 'owner_id', 'status',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    /**
     * Short display chip — uses the admin-supplied `abbreviation` if set,
     * otherwise falls back to initials of the scheme name (max 4 chars,
     * uppercase). Useful for compact dashboard rendering.
     */
    public function displayAbbreviation(): string
    {
        if (filled($this->abbreviation)) {
            return strtoupper($this->abbreviation);
        }

        $initials = collect(preg_split('/\s+/u', trim((string) $this->name)))
            ->filter()
            ->map(fn (string $word) => mb_substr($word, 0, 1))
            ->take(4)
            ->implode('');

        return strtoupper($initials !== '' ? $initials : (string) mb_substr((string) $this->name, 0, 2));
    }

    protected static function newFactory(): SchemeFactory
    {
        return SchemeFactory::new();
    }

    /**
     * Polymorphic attachments aren't FK-cascadable in MySQL/Postgres, so
     * we wipe them in app code when the parent goes away. Triggers each
     * Attachment's `deleting` hook so the blob on disk is removed too.
     * Tasks are iterated through Eloquent (not left to the DB cascade)
     * for the same reason — each task's own deleting hook must fire to
     * clean ITS attachment blobs.
     */
    protected static function booted(): void
    {
        static::deleting(function (Scheme $scheme): void {
            $scheme->tasks()->get()->each->delete();
            $scheme->attachments()->get()->each->delete();
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function attachments(): MorphMany
    {
        return $this->morphMany(Attachment::class, 'attachable')
            ->orderByDesc('created_at');
    }
}
