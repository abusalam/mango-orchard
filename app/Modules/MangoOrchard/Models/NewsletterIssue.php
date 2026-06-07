<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One row per newsletter issue. Stays a draft (sent_at = null) until an
 * admin clicks Send — at which point we queue the NewsletterIssued
 * notification to every opted-in subscriber and stamp sent_at +
 * sent_to_count for the audit trail.
 */
class NewsletterIssue extends Model
{
    protected $table = 'newsletter_issues';

    protected $fillable = ['subject', 'body', 'sent_at', 'sent_to_count', 'created_by'];

    protected $casts = [
        'sent_at' => 'datetime',
        'sent_to_count' => 'integer',
    ];

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isDraft(): bool
    {
        return $this->sent_at === null;
    }

    public function isSent(): bool
    {
        return $this->sent_at !== null;
    }

    public function scopeDrafts(Builder $q): Builder
    {
        return $q->whereNull('sent_at');
    }

    public function scopeSent(Builder $q): Builder
    {
        return $q->whereNotNull('sent_at')->orderByDesc('sent_at');
    }
}
