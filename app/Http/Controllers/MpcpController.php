<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\MpcpDocument;
use App\Models\MpcpSection;
use App\Mpcp\ContactCardParser;
use Illuminate\View\View;

class MpcpController extends Controller
{
    public function index(ContactCardParser $cards): View
    {
        $document = MpcpDocument::current();
        $sections = MpcpSection::query()
            ->where('published', true)
            ->orderBy('display_order')
            ->with(['entries'])
            ->get();

        // Card sections (§§5, 7) and the footer carry their data as markdown
        // blobs. Parse those into structured fields here so the view can hand
        // them to <x-mpcp.contact-card> unconditionally.
        $sectionCards = [];
        foreach ($sections->where('layout', 'card') as $section) {
            foreach ($section->entries as $entry) {
                $sectionCards[$entry->id] = $cards->parseBullets((string) ($entry->data['markdown'] ?? ''));
            }
        }

        $footerCards = $cards->parseFooter((string) $document->footer_md_en);

        return view('mpcp.index', [
            'document' => $document,
            'sections' => $sections,
            'sectionCards' => $sectionCards,
            'footerCards' => $footerCards,
        ]);
    }
}
