<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GalleryAlbum;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GalleryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::GALLERY_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.gallery.index', [
            'albums' => GalleryAlbum::query()
                ->orderBy('display_order')
                ->withCount('photos')
                ->with('coverPhoto')
                ->get(),
        ]);
    }

    public function create(): View
    {
        return view('admin.gallery.albums.create', [
            'album' => new GalleryAlbum,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateAlbum($request);

        $album = GalleryAlbum::query()->create([
            ...$data,
            'created_by' => $request->user()?->id,
        ]);

        app(Telemetry::class)->record('gallery.album_created', subject: $album);

        return redirect()->route('admin.gallery.albums.edit', $album)->with('status', 'Album created. Add photos below.');
    }

    public function edit(GalleryAlbum $album): View
    {
        return view('admin.gallery.albums.edit', [
            'album' => $album->load('photos'),
        ]);
    }

    public function update(Request $request, GalleryAlbum $album): RedirectResponse
    {
        $data = $this->validateAlbum($request, $album);

        $album->update($data);

        app(Telemetry::class)->record('gallery.album_updated', subject: $album);

        return redirect()->route('admin.gallery.albums.edit', $album)->with('status', 'Album saved.');
    }

    public function destroy(GalleryAlbum $album): RedirectResponse
    {
        $slug = $album->slug;
        $album->delete();

        app(Telemetry::class)->record('gallery.album_deleted', context: ['slug' => $slug]);

        return redirect()->route('admin.gallery.index')->with('status', 'Album deleted.');
    }

    private function validateAlbum(Request $request, ?GalleryAlbum $album = null): array
    {
        $data = $request->validate([
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                Rule::unique('gallery_albums', 'slug')->ignore($album?->id),
            ],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
        ]);

        $data['published'] = (bool) ($data['published'] ?? false);
        $data['display_order'] = (int) ($data['display_order'] ?? 0);

        return $data;
    }
}
