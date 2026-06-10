<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Gallery\ImagePipeline;
use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Models\GalleryPhoto;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

class GalleryPhotoController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::GALLERY_MANAGE]),
        ];
    }

    public function store(Request $request, GalleryAlbum $album, ImagePipeline $pipeline): RedirectResponse
    {
        $request->validate([
            'photos' => ['required', 'array', 'min:1', 'max:50'],
            'photos.*' => ['required', 'image', 'max:15360'], // 15 MB
        ]);

        $maxPosition = (int) $album->photos()->max('position');
        $count = 0;
        foreach ($request->file('photos') as $upload) {
            $stored = $pipeline->store($upload, $album->slug);

            $photo = $album->photos()->create([
                ...$stored,
                'alt_text' => $album->title,
                'position' => ++$maxPosition,
            ]);

            // Auto-pick first uploaded photo as cover if album has none yet.
            if ($album->cover_photo_id === null) {
                $album->update(['cover_photo_id' => $photo->id]);
            }
            $count++;
        }

        app(Telemetry::class)->record('gallery.photos_uploaded', subject: $album, context: ['count' => $count]);

        return back()->with('status', "{$count} photo".($count === 1 ? '' : 's').' uploaded.');
    }

    public function update(Request $request, GalleryAlbum $album, GalleryPhoto $photo): RedirectResponse
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);

        $data = $request->validate([
            'caption' => ['nullable', 'string', 'max:500'],
            'alt_text' => ['nullable', 'string', 'max:255'],
            'position' => ['nullable', 'integer', 'min:0'],
        ]);

        $photo->update(array_filter([
            'caption' => $data['caption'] ?? null,
            'alt_text' => $data['alt_text'] ?? null,
            'position' => $data['position'] ?? null,
        ], fn ($v) => $v !== null) + ['caption' => $data['caption'] ?? null, 'alt_text' => $data['alt_text'] ?? null]);

        return back()->with('status', 'Photo updated.');
    }

    public function setCover(GalleryAlbum $album, GalleryPhoto $photo): RedirectResponse
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);

        $album->update(['cover_photo_id' => $photo->id]);

        app(Telemetry::class)->record('gallery.cover_changed', subject: $album);

        return back()->with('status', 'Cover photo updated.');
    }

    public function destroy(GalleryAlbum $album, GalleryPhoto $photo): RedirectResponse
    {
        abort_unless($photo->gallery_album_id === $album->id, 404);

        // If we're deleting the album's cover, NULL it first so the FK
        // (cover_photo_id → photos.id) doesn't trigger a cascade weirdness.
        if ($album->cover_photo_id === $photo->id) {
            $album->update(['cover_photo_id' => null]);
        }

        $photo->delete();

        app(Telemetry::class)->record('gallery.photo_deleted');

        return back()->with('status', 'Photo deleted.');
    }
}
