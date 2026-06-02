<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAdvisoryRequest;
use App\Models\Advisory;
use App\Models\MangoVariety;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdvisoryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::ADVISORIES_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.advisories.index', [
            'advisories' => Advisory::with(['issuer', 'varieties'])
                ->latest('updated_at')
                ->paginate(20),
        ]);
    }

    public function create(): View
    {
        return view('admin.advisories.create', [
            'advisory' => new Advisory([
                'category' => Advisory::CATEGORY_BEST_PRACTICE,
                'severity' => Advisory::SEVERITY_INFO,
                'published' => false,
            ]),
            'varieties' => MangoVariety::orderBy('name')->get(),
            'selectedVarietyIds' => [],
        ]);
    }

    public function store(StoreAdvisoryRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $varietyIds = $data['mango_variety_ids'] ?? [];
        unset($data['mango_variety_ids']);

        $upload = $request->file('image');
        unset($data['image'], $data['remove_image']);

        if ($upload) {
            $data['image_path'] = $upload->store('advisories', 'public');
        }

        // If marked published and no explicit issued_at, stamp now so the
        // public-feed visibility checks pass.
        if (($data['published'] ?? false) && empty($data['issued_at'])) {
            $data['issued_at'] = now();
        }

        $advisory = Advisory::create([
            ...$data,
            'issued_by' => $request->user()->id,
        ]);

        $advisory->varieties()->sync($varietyIds);

        return redirect()
            ->route('admin.advisories.index')
            ->with('status', "Advisory \"{$advisory->title}\" saved.");
    }

    public function edit(Advisory $advisory): View
    {
        return view('admin.advisories.edit', [
            'advisory' => $advisory,
            'varieties' => MangoVariety::orderBy('name')->get(),
            'selectedVarietyIds' => $advisory->varieties()->pluck('mango_varieties.id')->all(),
        ]);
    }

    public function update(StoreAdvisoryRequest $request, Advisory $advisory): RedirectResponse
    {
        $data = $request->validated();
        $varietyIds = $data['mango_variety_ids'] ?? [];
        unset($data['mango_variety_ids']);

        $upload = $request->file('image');
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['image'], $data['remove_image']);

        if ($upload) {
            if ($advisory->image_path) {
                Storage::disk('public')->delete($advisory->image_path);
            }
            $data['image_path'] = $upload->store('advisories', 'public');
        } elseif ($removeImage && $advisory->image_path) {
            Storage::disk('public')->delete($advisory->image_path);
            $data['image_path'] = null;
        }

        if (($data['published'] ?? false) && empty($data['issued_at']) && $advisory->issued_at === null) {
            $data['issued_at'] = now();
        }

        $advisory->update($data);
        $advisory->varieties()->sync($varietyIds);

        return redirect()
            ->route('admin.advisories.index')
            ->with('status', "Advisory \"{$advisory->title}\" updated.");
    }

    public function destroy(Advisory $advisory): RedirectResponse
    {
        $title = $advisory->title;
        $advisory->delete();

        return redirect()
            ->route('admin.advisories.index')
            ->with('status', "Removed advisory \"{$title}\".");
    }
}
