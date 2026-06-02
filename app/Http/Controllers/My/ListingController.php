<?php

declare(strict_types=1);

namespace App\Http\Controllers\My;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreListingRequest;
use App\Http\Requests\UpdateListingRequest;
use App\Models\Listing;
use App\Models\MangoVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ListingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware('auth')];
    }

    public function index(): View
    {
        return view('my.listings.index', [
            'listings' => auth()->user()->listings()->with('variety')->latest('updated_at')->get(),
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Listing::class);

        return view('my.listings.create', [
            'listing' => new Listing([
                'status' => Listing::STATUS_DRAFT,
                'availability_start_month' => now()->month,
                'availability_end_month' => now()->addMonths(2)->month,
                'contact_email' => auth()->user()->email,
            ]),
            'varieties' => MangoVariety::query()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreListingRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Hoist the upload out of the validated array — Laravel's `image`
        // rule keeps it as an UploadedFile, but it shouldn't go to ->create().
        $upload = $request->file('image');
        unset($data['image']);

        if ($upload) {
            $data['image_path'] = $upload->store('listings', 'public');
        }

        $listing = auth()->user()->listings()->create($data);

        return redirect()
            ->route('my.listings.index')
            ->with('status', "Saved listing for {$listing->variety->name}.");
    }

    public function edit(Listing $listing): View
    {
        Gate::authorize('update', $listing);

        return view('my.listings.edit', [
            'listing' => $listing,
            'varieties' => MangoVariety::query()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateListingRequest $request, Listing $listing): RedirectResponse
    {
        $data = $request->validated();

        $upload = $request->file('image');
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($upload) {
            // New upload replaces any prior image — drop the old file.
            if ($listing->image_path) {
                Storage::disk('public')->delete($listing->image_path);
            }
            $data['image_path'] = $upload->store('listings', 'public');
        } elseif ($removeImage && $listing->image_path) {
            Storage::disk('public')->delete($listing->image_path);
            $data['image_path'] = null;
        }

        $listing->update($data);

        return redirect()
            ->route('my.listings.index')
            ->with('status', "Updated listing for {$listing->variety->name}.");
    }

    public function destroy(Listing $listing): RedirectResponse
    {
        Gate::authorize('delete', $listing);
        $name = $listing->variety->name;
        $listing->delete();

        return redirect()
            ->route('my.listings.index')
            ->with('status', "Removed listing for {$name}.");
    }
}
