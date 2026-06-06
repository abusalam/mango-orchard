<?php

declare(strict_types=1);

namespace App\Modules\SchemeMonitoring\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchemeMonitoring\Hierarchy;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Permissions;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SchemeController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [new Middleware(['auth', 'permission:'.Permissions::MONITORING_VIEW])];
    }

    public function index(Request $request, Hierarchy $hierarchy): View
    {
        $viewer = $request->user();
        $canManage = $viewer->can(Permissions::MONITORING_MANAGE);
        $visibleUserIds = $canManage ? null : $hierarchy->descendantUserIds($viewer->id);

        $search = trim((string) $request->query('q', ''));

        $schemes = Scheme::query()
            ->with(['owner', 'tasks'])
            ->when($visibleUserIds !== null, fn ($q) => $q->whereIn('owner_id', $visibleUserIds))
            // Free-text search over the columns a user is most likely to
            // recognise. ILIKE keeps it case-insensitive on Postgres.
            ->when($search !== '', function ($q) use ($search): void {
                $needle = '%'.$search.'%';
                $q->where(function ($q) use ($needle): void {
                    $q->where('name', 'ilike', $needle)
                        ->orWhere('abbreviation', 'ilike', $needle)
                        ->orWhere('description', 'ilike', $needle);
                });
            })
            ->orderByDesc('created_at')
            ->paginate(20)
            ->withQueryString();

        return view('scheme-monitoring::schemes.index', [
            'schemes' => $schemes,
            'canManage' => $canManage,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        Gate::authorize('create', Scheme::class);

        return view('scheme-monitoring::schemes.create', [
            'scheme' => new Scheme(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Scheme::class);

        $data = $this->validated($request);

        $scheme = Scheme::create([
            ...$data,
            'owner_id' => $request->user()->id,
        ]);

        // Redirect to the edit page so the creator can immediately add
        // attachments, an abbreviation, end-date, etc. — the index doesn't
        // expose those follow-ups.
        return redirect()
            ->route('monitoring.schemes.edit', $scheme)
            ->with('status', "Scheme '{$scheme->name}' created.");
    }

    public function show(Scheme $scheme): View
    {
        Gate::authorize('view', $scheme);

        $scheme->load(['owner', 'tasks.assignee']);

        return view('scheme-monitoring::schemes.show', [
            'scheme' => $scheme,
        ]);
    }

    public function edit(Scheme $scheme): View
    {
        Gate::authorize('update', $scheme);

        return view('scheme-monitoring::schemes.edit', [
            'scheme' => $scheme,
        ]);
    }

    public function update(Request $request, Scheme $scheme): RedirectResponse
    {
        Gate::authorize('update', $scheme);

        $scheme->update($this->validated($request));

        return redirect()
            ->route('monitoring.schemes.show', $scheme)
            ->with('status', 'Scheme updated.');
    }

    public function destroy(Scheme $scheme): RedirectResponse
    {
        Gate::authorize('delete', $scheme);

        $name = $scheme->name;
        $scheme->delete();

        return redirect()
            ->route('monitoring.schemes.index')
            ->with('status', "Scheme '{$name}' deleted.");
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'abbreviation' => ['nullable', 'string', 'max:12'],
            'description' => ['nullable', 'string'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['required', 'in:'.implode(',', array_keys(Scheme::STATUSES))],
        ]);
    }
}
