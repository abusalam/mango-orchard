<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;

    protected $table = 'monitoring_attachments';

    protected $fillable = [
        'attachable_type', 'attachable_id',
        'uploaded_by',
        'original_name', 'path', 'mime_type', 'size_bytes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    protected static function newFactory(): \Database\Factories\SchemeMonitoring\AttachmentFactory
    {
        return \Database\Factories\SchemeMonitoring\AttachmentFactory::new();
    }

    protected static function booted(): void
    {
        // Remove the blob from disk when the row goes away — keeps the
        // public disk in sync with the DB whether deletion comes from a
        // user action or a cascade triggered by deleting the parent.
        static::deleting(function (Attachment $attachment): void {
            if ($attachment->path !== '' && Storage::disk('public')->exists($attachment->path)) {
                Storage::disk('public')->delete($attachment->path);
            }
        });
    }

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function url(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Compact human-readable size — "12.4 KB", "3.1 MB", etc.
     */
    public function humanSize(): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;
        $i = 0;
        while ($size >= 1024 && $i < count($units) - 1) {
            $size /= 1024;
            $i++;
        }

        return round($size, $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
