<?php

declare(strict_types=1);

namespace App\Mpcp;

use Illuminate\Support\Str;

/**
 * Parses the bundled MPCP markdown (database/seeders/data/mpcp.md) into
 * structured arrays the seeder can persist.
 *
 * The parser is deliberately not a full markdown AST — it walks the file
 * by H1/H2/H3 headings and table/bullet boundaries, which matches the
 * bundled source's stable shape.
 *
 * Returns:
 *   [
 *     'document' => [
 *       'title_en', 'title_bn',
 *       'attribution_md_en', 'attribution_md_bn',
 *       'about_md_en', 'about_md_bn',
 *       'footer_md_en', 'footer_md_bn',
 *       'website_url',
 *     ],
 *     'sections' => [
 *       [
 *         'slug', 'title_en', 'title_bn',
 *         'intro_md_en', 'intro_md_bn',
 *         'layout',     // 'table' | 'card'
 *         'columns' => [['key', 'label_en', 'label_bn', 'type'], ...],
 *         'display_order',
 *         'entries' => [['data' => [key => value]], ...],
 *       ],
 *       ...
 *     ],
 *   ]
 */
class MarkdownParser
{
    public function parse(string $markdown): array
    {
        [$frontMatter, $body] = $this->splitFrontMatter($markdown);

        $document = [
            'title_en' => $frontMatter['title'] ?? 'Mango Promotion Communication Plan (MPCP)',
            'title_bn' => $frontMatter['title_bn'] ?? null,
            'website_url' => $frontMatter['website'] ?? null,
            'attribution_md_en' => null,
            'attribution_md_bn' => null,
            'about_md_en' => null,
            'about_md_bn' => null,
            'footer_md_en' => null,
            'footer_md_bn' => null,
        ];

        $blocks = $this->splitByH2($body);

        // The body before the first H2 holds the H1 title pair + blockquote.
        $preamble = $blocks['__preamble'] ?? '';
        unset($blocks['__preamble']);

        $document['attribution_md_en'] = $this->extractBlockquote($preamble) ?? $document['attribution_md_en'];

        // Pre-H2 title pair: # H1 then ## H2 bengali — we treat that ## as
        // the BN of the document (not a real H2 section). The blockquote
        // captures the attribution lines beneath.
        if (preg_match('/^\#\s+(.+)$/mu', $preamble, $m)) {
            $document['title_en'] = trim($m[1]);
        }
        if (preg_match('/^\#\#\s+(.+)$/mu', $preamble, $m)) {
            $document['title_bn'] = trim($m[1]);
        }

        $sections = [];
        $displayOrder = 0;

        foreach ($blocks as $heading => $block) {
            [$en, $bn] = $this->splitBilingual($heading);

            if (str_contains(Str::lower($en), 'about this communication plan')) {
                [$enBody, $bnBody] = $this->splitBilingualParagraphs($block);
                $document['about_md_en'] = $enBody;
                $document['about_md_bn'] = $bnBody;

                continue;
            }

            if (str_contains(Str::lower($en), 'contents')) {
                continue;
            }

            if (str_contains(Str::lower($en), 'issued by')) {
                // The Issued-by block is captured verbatim as the footer.
                // BN footer isn't separated in the source; we keep both
                // languages in footer_md_en so admins can split later.
                $document['footer_md_en'] = trim($block);

                continue;
            }

            // Numbered section: "## 1. Foo / বার"
            if (! preg_match('/^(\d+)\.\s+(.+)$/u', $en, $matches)) {
                continue;
            }

            $sectionNumber = (int) $matches[1];
            $titleEn = trim($matches[2]);
            $titleBn = $bn;

            $section = [
                'slug' => Str::slug($titleEn),
                'title_en' => $titleEn,
                'title_bn' => $titleBn,
                'intro_md_en' => $this->extractIntro($block),
                'intro_md_bn' => null,
                'display_order' => $sectionNumber,
                'entries' => [],
            ];

            if ($this->blockHasTable($block)) {
                [$columns, $entries] = $this->parseTable($block);
                $section['layout'] = 'table';
                $section['columns'] = $columns;
                $section['entries'] = $entries;
            } else {
                // Bullet-list contact card. Stored as a single markdown blob
                // so admins can edit it as a single textarea.
                $section['layout'] = 'card';
                $section['columns'] = [
                    [
                        'key' => 'markdown',
                        'label_en' => 'Card body (markdown)',
                        'label_bn' => 'কার্ডের বিষয়বস্তু',
                        'type' => 'long_text',
                    ],
                ];
                $section['entries'] = [
                    ['data' => ['markdown' => trim($block)]],
                ];
            }

            $sections[] = $section;
            $displayOrder++;
        }

        return [
            'document' => $document,
            'sections' => $sections,
        ];
    }

    /** @return array{0: array<string,string>, 1: string} */
    private function splitFrontMatter(string $markdown): array
    {
        if (! preg_match('/^---\s*\n(.*?)\n---\s*\n(.*)$/su', $markdown, $m)) {
            return [[], $markdown];
        }

        $rawFront = $m[1];
        $body = $m[2];

        $front = [];
        foreach (preg_split('/\R/u', $rawFront) as $line) {
            if (! preg_match('/^([a-zA-Z_]+):\s*(.+)$/u', trim($line), $kv)) {
                continue;
            }
            $front[$kv[1]] = trim($kv[2]);
        }

        return [$front, $body];
    }

    /**
     * Splits the body by H2 headings. Returns ['__preamble' => …, heading => block, …].
     * Block text excludes the heading line itself.
     */
    private function splitByH2(string $body): array
    {
        $blocks = ['__preamble' => ''];
        $currentKey = '__preamble';

        foreach (preg_split('/\R/u', $body) as $line) {
            if (preg_match('/^\#\#\s+(.+)$/u', $line, $m)) {
                $currentKey = trim($m[1]);
                $blocks[$currentKey] = '';

                continue;
            }
            $blocks[$currentKey] .= $line."\n";
        }

        return $blocks;
    }

    /** Splits "English / বাংলা" into [en, bn]. Returns [text, null] if no " / " separator. */
    private function splitBilingual(string $heading): array
    {
        if (str_contains($heading, ' / ')) {
            [$en, $bn] = explode(' / ', $heading, 2);

            return [trim($en), trim($bn)];
        }

        return [trim($heading), null];
    }

    /** Splits two paragraphs (separated by blank line) into [en, bn]. */
    private function splitBilingualParagraphs(string $block): array
    {
        $paragraphs = preg_split('/\n\s*\n/u', trim($block), 2);

        return [
            isset($paragraphs[0]) ? trim($paragraphs[0]) : null,
            isset($paragraphs[1]) ? trim($paragraphs[1]) : null,
        ];
    }

    private function extractBlockquote(string $block): ?string
    {
        $lines = preg_grep('/^>/u', preg_split('/\R/u', $block));

        if (empty($lines)) {
            return null;
        }

        // Strip leading "> " and trailing "  " (markdown's hard-break).
        $stripped = array_map(fn ($l) => preg_replace('/^>\s?/u', '', rtrim($l)), $lines);

        return trim(implode("\n", $stripped));
    }

    private function extractIntro(string $block): ?string
    {
        // First italic line in the block — sections' source notes.
        foreach (preg_split('/\R/u', $block) as $line) {
            $line = trim($line);
            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }
            if (preg_match('/^\*(.+)\*$/u', $line, $m)) {
                return trim($m[1]);
            }

            return null;
        }

        return null;
    }

    private function blockHasTable(string $block): bool
    {
        foreach (preg_split('/\R/u', $block) as $line) {
            if (preg_match('/^\|.+\|$/u', trim($line))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Parses a markdown table inside the block. Returns [columns, entries].
     * Column types are inferred from header labels (email/tel/long_text/text).
     */
    private function parseTable(string $block): array
    {
        $tableLines = [];
        $inTable = false;
        foreach (preg_split('/\R/u', $block) as $line) {
            $trimmed = trim($line);
            if (preg_match('/^\|.+\|$/u', $trimmed)) {
                $tableLines[] = $trimmed;
                $inTable = true;
            } elseif ($inTable && $trimmed === '') {
                break;
            }
        }

        if (count($tableLines) < 3) {
            return [[], []];
        }

        $headerCells = $this->splitRow($tableLines[0]);
        // Line 1 is the "| --- | --- |" separator; skip.
        $bodyLines = array_slice($tableLines, 2);

        $columns = [];
        foreach ($headerCells as $idx => $label) {
            [$labelEn, $labelBn] = $this->splitBilingual($label);
            // Drop the "#" auto-numbered column — entries already have a position field.
            if ($labelEn === '#') {
                continue;
            }

            $columns[$idx] = [
                'key' => $this->columnKey($labelEn),
                'label_en' => $labelEn,
                'label_bn' => $labelBn,
                'type' => $this->inferType($labelEn),
            ];
        }

        $entries = [];
        $position = 0;
        foreach ($bodyLines as $row) {
            $cells = $this->splitRow($row);
            $data = [];
            foreach ($columns as $idx => $col) {
                $value = trim($cells[$idx] ?? '');
                // Replace em-dash placeholders with empty string.
                if ($value === '—' || $value === '-') {
                    $value = '';
                }
                $data[$col['key']] = $value;
            }
            $entries[] = ['data' => $data, 'position' => ++$position];
        }

        return [array_values($columns), $entries];
    }

    /** @return string[] */
    private function splitRow(string $row): array
    {
        // Trim outer pipes, split on inner pipes.
        $row = trim($row);
        $row = preg_replace('/^\|/u', '', $row);
        $row = preg_replace('/\|$/u', '', $row);

        return array_map('trim', explode('|', $row));
    }

    private function columnKey(string $labelEn): string
    {
        $key = Str::slug($labelEn, '_');

        return $key === '' ? 'value' : $key;
    }

    private function inferType(string $labelEn): string
    {
        $lower = Str::lower($labelEn);

        return match (true) {
            str_contains($lower, 'email') => 'email',
            str_contains($lower, 'mobile'), str_contains($lower, 'phone') => 'tel',
            str_contains($lower, 'address'),
            str_contains($lower, 'contact'),
            str_contains($lower, 'products'),
            str_contains($lower, 'suitable') => 'long_text',
            default => 'text',
        };
    }
}
