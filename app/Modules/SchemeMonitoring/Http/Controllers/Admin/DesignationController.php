<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\SchemeMonitoring\Models\Designation;
use App\Permissions;
use Closure;
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
            'designations' => Designation::with('parent')
                ->orderByDesc('level')->orderBy('name')->paginate(50),
        ]);
    }

    public function create(): View
    {
        return view('scheme-monitoring::admin.designations.create', [
            'designation' => new Designation(),
            'parentOptions' => Designation::orderByDesc('level')->orderBy('name')->get(['id', 'name']),
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
        // Hide the designation itself + its descendants from the parent
        // picker so an admin can't introduce a cycle through the dropdown.
        $descendantIds = $designation->descendantIds();

        return view('scheme-monitoring::admin.designations.edit', [
            'designation' => $designation,
            'parentOptions' => Designation::query()
                ->whereNotIn('id', $descendantIds)
                ->orderByDesc('level')->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    public function update(Request $request, Designation $designation): RedirectResponse
    {
        $designation->update($this->validated($request, $designation));

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

    private function validated(Request $request, ?Designation $existing = null): array
    {
        $unique = 'unique:monitoring_designations,name'.($existing ? ','.$existing->id : '');

        // Cycle guard: when editing, the chosen parent can't be the
        // designation itself or any of its descendants. The form already
        // hides those, but a hand-crafted POST shouldn't bypass it.
        $forbiddenParents = $existing ? $existing->descendantIds() : [];

        return $request->validate([
            'name' => ['required', 'string', 'max:120', $unique],
            'description' => ['nullable', 'string', 'max:1000'],
            'level' => ['required', 'integer', 'min:0', 'max:100'],
            'parent_id' => [
                'nullable', 'integer', 'exists:monitoring_designations,id',
                function (string $attr, mixed $value, Closure $fail) use ($forbiddenParents): void {
                    if ($value !== null && in_array((int) $value, $forbiddenParents, true)) {
                        $fail('A designation cannot report to itself or one of its own descendants.');
                    }
                },
            ],
        ]);
    }
}
