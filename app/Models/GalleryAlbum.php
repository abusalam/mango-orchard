<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class GalleryAlbum extends Model
{
    protected $fillable = [
        'slug',
        'title',
        'description',
        'cover_photo_id',
        'display_order',
        'published',
        'created_by',
    ];

    protected $casts = [
        'published' => 'boolean',
        'display_order' => 'integer',
    ];

    protected static function booted(): void
    {
        // Polymorphic-style cascade — albums cascade to photos at the DB
        // level, but iterate so each photo's deleting hook runs to wipe
        // the disk blob too (same pattern as Scheme attachments).
        static::deleting(function (GalleryAlbum $album): void {
            $album->photos->each->delete();
        });
    }

    public function photos(): HasMany
    {
        return $this->hasMany(GalleryPhoto::class)->orderBy('position');
    }

    public function coverPhoto(): BelongsTo
    {
        return $this->belongsTo(GalleryPhoto::class, 'cover_photo_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}
