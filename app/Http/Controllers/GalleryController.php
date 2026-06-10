<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\GalleryAlbum;
use Illuminate\View\View;

class GalleryController extends Controller
{
    public function index(): View
    {
        return view('gallery.index', [
            'albums' => GalleryAlbum::query()
                ->where('published', true)
                ->orderBy('display_order')
                ->withCount('photos')
                ->with('coverPhoto')
                ->get(),
        ]);
    }

    public function show(GalleryAlbum $album): View
    {
        abort_unless($album->published, 404);

        return view('gallery.show', [
            'album' => $album->load('photos'),
        ]);
    }
}
