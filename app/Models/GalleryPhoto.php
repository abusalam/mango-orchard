<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class GalleryPhoto extends Model
{
    protected $fillable = [
        'gallery_album_id',
        'path',
        'thumbnail_path',
        'caption',
        'alt_text',
        'position',
        'width',
        'height',
    ];

    protected $casts = [
        'position' => 'integer',
        'width' => 'integer',
        'height' => 'integer',
    ];

    protected static function booted(): void
    {
        // Disk cleanup — original + thumbnail. Runs from the model
        // delete() so admin destroy + cascaded delete (via album
        // booted()) both wipe the blob.
        static::deleting(function (GalleryPhoto $photo): void {
            foreach (array_filter([$photo->path, $photo->thumbnail_path]) as $path) {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            }
        });
    }

    public function album(): BelongsTo
    {
        return $this->belongsTo(GalleryAlbum::class, 'gallery_album_id');
    }

    public function url(): ?string
    {
        return $this->path ? Storage::disk('public')->url($this->path) : null;
    }

    public function thumbnailUrl(): ?string
    {
        return $this->thumbnail_path
            ? Storage::disk('public')->url($this->thumbnail_path)
            : $this->url();
    }
}
