<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ListingController extends Controller
{
    public function index(Request $request): View
    {
        $filterVarietyId = $request->integer('variety') ?: null;

        $listings = Listing::query()
            ->visible()
            ->with(['user', 'variety'])
            ->when($filterVarietyId, fn ($q, $id) => $q->where('mango_variety_id', $id))
            ->latest('updated_at')
            ->paginate(12)
            ->withQueryString();

        return view('listings.index', [
            'listings' => $listings,
            'filterVarietyId' => $filterVarietyId,
            'varieties' => MangoVariety::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function show(Listing $listing): View
    {
        abort_unless(
            in_array($listing->status, [Listing::STATUS_PUBLISHED, Listing::STATUS_SOLD_OUT], true)
                || ($listing->user_id === auth()->id()),
            404,
        );

        return view('listings.show', [
            'listing' => $listing->load(['user', 'variety']),
        ]);
    }
}
