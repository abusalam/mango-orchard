<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreMangoVarietyRequest;
use App\Http\Requests\UpdateMangoVarietyRequest;
use App\Models\MangoVariety;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MangoVarietyController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth', except: ['index', 'show']),
            new Middleware('permission:'.\App\Permissions::VARIETIES_MANAGE, except: ['index', 'show']),
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
        $variety = MangoVariety::create($request->validated());

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
        $variety->update($request->validated());

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
