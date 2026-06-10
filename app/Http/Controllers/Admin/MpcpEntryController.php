<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MpcpEntry;
use App\Models\MpcpSection;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MpcpEntryController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::MPCP_MANAGE]),
        ];
    }

    public function index(MpcpSection $section): View
    {
        return view('admin.mpcp.entries.index', [
            'section' => $section,
            'entries' => $section->entries()->paginate(50),
        ]);
    }

    public function create(MpcpSection $section): View
    {
        return view('admin.mpcp.entries.create', [
            'section' => $section,
            'entry' => new MpcpEntry(['data' => []]),
        ]);
    }

    public function store(Request $request, MpcpSection $section): RedirectResponse
    {
        $data = $this->validateEntry($request, $section);

        $entry = $section->entries()->create([
            'data' => $data['data'],
            'position' => $data['position'] ?? ($section->entries()->max('position') + 1),
        ]);

        app(Telemetry::class)->record('mpcp.entry_created', subject: $entry);

        return redirect()->route('admin.mpcp.entries.index', $section)->with('status', 'Entry added.');
    }

    public function edit(MpcpSection $section, MpcpEntry $entry): View
    {
        abort_unless($entry->mpcp_section_id === $section->id, 404);

        return view('admin.mpcp.entries.edit', [
            'section' => $section,
            'entry' => $entry,
        ]);
    }

    public function update(Request $request, MpcpSection $section, MpcpEntry $entry): RedirectResponse
    {
        abort_unless($entry->mpcp_section_id === $section->id, 404);

        $data = $this->validateEntry($request, $section);

        $entry->update([
            'data' => $data['data'],
            'position' => $data['position'] ?? $entry->position,
        ]);

        app(Telemetry::class)->record('mpcp.entry_updated', subject: $entry);

        return redirect()->route('admin.mpcp.entries.index', $section)->with('status', 'Entry saved.');
    }

    public function destroy(MpcpSection $section, MpcpEntry $entry): RedirectResponse
    {
        abort_unless($entry->mpcp_section_id === $section->id, 404);

        $entry->delete();

        app(Telemetry::class)->record('mpcp.entry_deleted');

        return redirect()->route('admin.mpcp.entries.index', $section)->with('status', 'Entry deleted.');
    }

    private function validateEntry(Request $request, MpcpSection $section): array
    {
        $rules = ['position' => ['nullable', 'integer', 'min:0']];

        foreach ($section->columns as $col) {
            $key = "data.{$col['key']}";
            $rules[$key] = match ($col['type']) {
                'email' => ['nullable', 'email', 'max:255'],
                'tel' => ['nullable', 'string', 'max:120'],
                'long_text' => ['nullable', 'string'],
                default => ['nullable', 'string', 'max:255'],
            };
        }

        $data = $request->validate($rules);

        // Normalise: fill missing keys with empty string so the JSON is stable.
        foreach ($section->columns as $col) {
            $data['data'][$col['key']] ??= '';
        }

        return $data;
    }
}
