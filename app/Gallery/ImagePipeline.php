<?php

declare(strict_types=1);

namespace App\Gallery;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;

/**
 * Encodes an uploaded image into a 1600px-wide WebP "original" plus a
 * 600px-wide WebP thumbnail. Both stored on the public disk under
 * gallery/{album-slug}/. Returns the relative paths + dimensions so the
 * caller can persist them on the GalleryPhoto row.
 */
class ImagePipeline
{
    private const MAX_WIDTH = 1600;

    private const THUMB_WIDTH = 600;

    private const QUALITY = 85;

    private ImageManager $images;

    public function __construct()
    {
        // intervention/image v3 needs an explicit driver; GD is in every
        // PHP build, no extension install needed.
        $this->images = ImageManager::gd();
    }

    /**
     * @return array{path: string, thumbnail_path: string, width: int, height: int}
     */
    public function store(UploadedFile $file, string $albumSlug): array
    {
        $disk = Storage::disk('public');
        $dir = "gallery/{$albumSlug}";
        $base = Str::random(16);

        $image = $this->images->read($file->getRealPath());
        // scaleDown preserves aspect ratio + never upscales.
        $image->scaleDown(width: self::MAX_WIDTH);
        $width = $image->width();
        $height = $image->height();

        $originalPath = "{$dir}/{$base}.webp";
        $disk->put($originalPath, (string) $image->toWebp(quality: self::QUALITY));

        $thumb = $this->images->read($file->getRealPath())->scaleDown(width: self::THUMB_WIDTH);
        $thumbnailPath = "{$dir}/{$base}_thumb.webp";
        $disk->put($thumbnailPath, (string) $thumb->toWebp(quality: self::QUALITY));

        return [
            'path' => $originalPath,
            'thumbnail_path' => $thumbnailPath,
            'width' => $width,
            'height' => $height,
        ];
    }
}
