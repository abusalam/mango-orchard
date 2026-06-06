<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\MangoOrchard\Models\Advisory;
use App\Modules\MangoOrchard\Models\MangoVariety;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Public read-only view of issued advisories. Anyone (logged in or not)
 * can browse; only published + currently-active advisories are listed.
 * Management lives at `/admin/advisories/*` behind `advisories.manage`.
 */
class AdvisoryController extends Controller
{
    public function index(Request $request): View
    {
        $filterCategory = (string) $request->string('category')->toString();
        $filterVarietyId = (int) $request->integer('variety');

        $query = Advisory::query()
            ->with(['issuer', 'varieties'])
            ->active()
            ->when(
                in_array($filterCategory, array_keys(Advisory::CATEGORIES), true),
                fn ($q) => $q->where('category', $filterCategory),
            )
            ->when(
                $filterVarietyId > 0,
                fn ($q) => $q->whereHas('varieties', fn ($v) => $v->where('mango_varieties.id', $filterVarietyId)),
            )
            // Urgent first, then warning, then info; tie-break by recency.
            ->orderByRaw("CASE severity WHEN 'urgent' THEN 3 WHEN 'warning' THEN 2 ELSE 1 END DESC")
            ->latest('issued_at');

        return view('advisories.index', [
            'advisories' => $query->paginate(15)->withQueryString(),
            'varieties' => MangoVariety::orderBy('name')->get(),
            'filterCategory' => $filterCategory,
            'filterVarietyId' => $filterVarietyId,
        ]);
    }

    public function show(Advisory $advisory): View
    {
        // Drafts / expired / future-issue items are only visible to the
        // issuer or someone with manage perms.
        abort_unless(
            $advisory->published
                && ($advisory->issued_at === null || $advisory->issued_at->isPast())
                && (! $advisory->isExpired())
                || auth()->user()?->can(\App\Permissions::ADVISORIES_MANAGE),
            404,
        );

        $advisory->load(['issuer', 'varieties']);

        return view('advisories.show', [
            'advisory' => $advisory,
        ]);
    }
}
