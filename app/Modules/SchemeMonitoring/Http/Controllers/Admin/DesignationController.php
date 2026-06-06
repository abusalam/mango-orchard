<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class DesignationController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_MANAGE])];
    }

    public function index(): View
    {
        return view('scheme-monitoring::admin.designations.index', [
            'designations' => Designation::orderByDesc('level')->orderBy('name')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('scheme-monitoring::admin.designations.create', [
            'designation' => new Designation(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Designation::create($this->validated($request));

        return redirect()
            ->route('admin.monitoring.designations.index')
            ->with('status', 'Designation created.');
    }

    public function edit(Designation $designation): View
    {
        return view('scheme-monitoring::admin.designations.edit', [
            'designation' => $designation,
        ]);
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $designation->update($this->validated($request, $designation->id));

        return redirect()
            ->route('admin.monitoring.designations.index')
            ->with('status', 'Designation updated.');
    }

    public function destroy(Designation $designation): RedirectResponse
    {
        $designation->delete();

        return redirect()
            ->route('admin.monitoring.designations.index')
            ->with('status', 'Designation deleted.');
    }

    private function validated(Request $request, ?int $ignoreId = null): array
    {
        $unique = 'unique:monitoring_designations,name'.($ignoreId ? ','.$ignoreId : '');

        return $request->validate([
            'name' => ['required', 'string', 'max:120', $unique],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'integer', 'min:0', 'max:100'],
        ]);
    }
}
