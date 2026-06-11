<?php

declare(strict_types=1);

namespace App\Modules\MangoOrchard\Http\Controllers;

use App\Http\Requests\StoreMangoVarietyRequest;
use App\Http\Requests\UpdateMangoVarietyRequest;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use App\Http\Controllers\Controller;

class MangoVarietyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['index', 'show']),
            new Middleware('permission:'.Permissions::VARIETIES_MANAGE, except: ['index', 'show']),
        ];
    }

    public function index(): View
    {
        return view('varieties.index', [
            'varieties' => MangoVariety::query()->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('varieties.create', [
            'variety' => new MangoVariety(['theme' => 'sunrise']),
        ]);
    }

    public function store(StoreMangoVarietyRequest $request): RedirectResponse
    {
        $data = $request->validated();
        unset($data['remove_image'], $data['image']);

        if ($upload = $request->file('image')) {
            $data['image_path'] = $upload->store('varieties', 'public');
        }

        $variety = MangoVariety::create($data);

        return redirect()
            ->route('varieties.show', $variety)
            ->with('status', "Added {$variety->name}.");
    }

    public function show(MangoVariety $variety): View
    {
        return view('varieties.show', [
            'variety' => $variety,
        ]);
    }

    public function edit(MangoVariety $variety): View
    {
        return view('varieties.edit', [
            'variety' => $variety,
        ]);
    }

    public function update(UpdateMangoVarietyRequest $request, MangoVariety $variety): RedirectResponse
    {
        $data = $request->validated();
        $removeImage = (bool) ($data['remove_image'] ?? false);
        unset($data['remove_image'], $data['image']);

        if ($upload = $request->file('image')) {
            if ($variety->image_path) {
                Storage::disk('public')->delete($variety->image_path);
            }
            $data['image_path'] = $upload->store('varieties', 'public');
        } elseif ($removeImage && $variety->image_path) {
            Storage::disk('public')->delete($variety->image_path);
            $data['image_path'] = null;
        }

        $variety->update($data);

        return redirect()
            ->route('varieties.show', $variety)
            ->with('status', "Updated {$variety->name}.");
    }

    public function destroy(MangoVariety $variety): RedirectResponse
    {
        $name = $variety->name;
        $variety->delete();

        return redirect()
            ->route('varieties.index')
            ->with('status', "Removed {$name}.");
    }
}
