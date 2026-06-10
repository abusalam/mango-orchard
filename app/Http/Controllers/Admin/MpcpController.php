<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\MpcpDocument;
use App\Models\MpcpSection;
use App\Permissions;
use App\Telemetry\Telemetry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\View\View;

class MpcpController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware(['auth', 'permission:'.Permissions::MPCP_MANAGE]),
        ];
    }

    public function index(): View
    {
        return view('admin.mpcp.index', [
            'document' => MpcpDocument::current(),
            'sections' => MpcpSection::query()
                ->orderBy('display_order')
                ->withCount('entries')
                ->get(),
        ]);
    }

    public function editDocument(): View
    {
        return view('admin.mpcp.document', [
            'document' => MpcpDocument::current(),
        ]);
    }

    public function updateDocument(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'attribution_md_en' => ['nullable', 'string'],
            'attribution_md_bn' => ['nullable', 'string'],
            'about_md_en' => ['nullable', 'string'],
            'about_md_bn' => ['nullable', 'string'],
            'footer_md_en' => ['nullable', 'string'],
            'footer_md_bn' => ['nullable', 'string'],
            'website_url' => ['nullable', 'string', 'url', 'max:255'],
        ]);

        MpcpDocument::current()->update($data);

        app(Telemetry::class)->record('mpcp.document_updated');

        return redirect()->route('admin.mpcp.document.edit')->with('status', 'Document saved.');
    }

    public function create(): View
    {
        return view('admin.mpcp.sections.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateSection($request);

        $section = MpcpSection::query()->create([
            ...$data,
            'created_by' => $request->user()?->id,
        ]);

        app(Telemetry::class)->record('mpcp.section_created', subject: $section);

        return redirect()->route('admin.mpcp.sections.edit', $section)->with('status', 'Section created.');
    }

    public function edit(MpcpSection $section): View
    {
        return view('admin.mpcp.sections.edit', [
            'section' => $section,
        ]);
    }

    public function update(Request $request, MpcpSection $section): RedirectResponse
    {
        $data = $this->validateSection($request, $section);

        $section->update($data);

        app(Telemetry::class)->record('mpcp.section_updated', subject: $section);

        return redirect()->route('admin.mpcp.sections.edit', $section)->with('status', 'Section saved.');
    }

    public function destroy(MpcpSection $section): RedirectResponse
    {
        $section->delete();

        app(Telemetry::class)->record('mpcp.section_deleted', context: ['slug' => $section->slug]);

        return redirect()->route('admin.mpcp.index')->with('status', 'Section deleted.');
    }

    /**
     * Validates section + parses the columns repeater payload from the form
     * (rendered as columns[0][key], columns[0][label_en], …).
     */
    private function validateSection(Request $request, ?MpcpSection $section = null): array
    {
        $data = $request->validate([
            'slug' => [
                'required', 'string', 'max:120', 'regex:/^[a-z0-9-]+$/',
                \Illuminate\Validation\Rule::unique('mpcp_sections', 'slug')->ignore($section?->id),
            ],
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'intro_md_en' => ['nullable', 'string'],
            'intro_md_bn' => ['nullable', 'string'],
            'layout' => ['required', 'in:table,card'],
            'display_order' => ['nullable', 'integer', 'min:0'],
            'published' => ['nullable', 'boolean'],
            'columns' => ['required', 'array', 'min:1'],
            'columns.*.key' => ['required', 'string', 'regex:/^[a-z0-9_]+$/'],
            'columns.*.label_en' => ['required', 'string', 'max:120'],
            'columns.*.label_bn' => ['nullable', 'string', 'max:120'],
            'columns.*.type' => ['required', 'in:text,tel,email,long_text'],
        ]);

        $data['published'] = (bool) ($data['published'] ?? false);
        $data['display_order'] = (int) ($data['display_order'] ?? 0);
        $data['columns'] = array_values($data['columns']);

        return $data;
    }
}
