<?php

declare(strict_types=1);

namespace App\Mpcp;

/**
 * Pulls structured contact fields (name, designation, organization,
 * address, phone, email, note) out of a markdown bullet list of the shape
 * the MPCP source uses:
 *
 *   - **Shri Rajanvir Singh Kapur, IAS** — District Magistrate & Collector
 *   - Office of the District Magistrate & Collector
 *   - **Address:** New Collectorate Building, 1st Floor, ...
 *   - **Phone:** (03512) 252 381 · **Email:** dm-malda@nic.in
 *
 * Used by the public /mpcp page for §§5, 7 (single-contact card sections)
 * and for the footer "Issued by" block (DM + Nodal Officer cards).
 */
class ContactCardParser
{
    /**
     * Parse a single bullet-list contact block.
     *
     * @return array{name: ?string, designation: ?string, organization: ?string, address: ?string, phone: ?string, email: ?string, note: ?string}
     */
    public function parseBullets(string $markdown): array
    {
        $card = [
            'name' => null,
            'designation' => null,
            'organization' => null,
            'address' => null,
            'phone' => null,
            'email' => null,
            'note' => null,
        ];

        foreach (preg_split('/\R/u', $markdown) as $line) {
            $line = trim($line);
            if (! str_starts_with($line, '- ')) {
                continue;
            }
            $body = ltrim(substr($line, 2));

            // **Name** — Designation [/ BN]
            if ($card['name'] === null && preg_match('/^\*\*(.+?)\*\*\s*[—–-]\s*(.+)$/u', $body, $m)) {
                $card['name'] = trim($m[1]);
                $card['designation'] = $this->stripBilingualTail(trim($m[2]));

                continue;
            }

            // **Address:** value (handles "Address / ঠিকানা:" too)
            if (preg_match('/^\*\*Address[^:]*:\*\*\s*(.+)$/u', $body, $m)) {
                $card['address'] = trim($m[1]);

                continue;
            }

            // **Phone:** v [· **Email:** e]
            if (preg_match('/^\*\*Phone[^:]*:\*\*\s*(.+)$/u', $body, $m)) {
                $rest = trim($m[1]);
                if (preg_match('/^(.+?)\s*·\s*\*\*Email[^:]*:\*\*\s*(.+)$/u', $rest, $em)) {
                    $card['phone'] = trim($em[1]);
                    $card['email'] = trim($em[2]);
                } elseif (preg_match('/^(.+?)\s*·\s*\*\*Telefax[^:]*:\*\*\s*(.+)$/u', $rest, $em)) {
                    $card['phone'] = trim($em[1]).' · '.trim($em[2]);
                } else {
                    $card['phone'] = $rest;
                }

                continue;
            }

            // **Email:** value (standalone)
            if (preg_match('/^\*\*Email[^:]*:\*\*\s*(.+)$/u', $body, $m)) {
                $card['email'] = trim($m[1]);

                continue;
            }

            // Italic line → note (e.g. "*Issues phytosanitary certification...*")
            if (preg_match('/^\*(.+)\*$/u', $body, $m)) {
                $card['note'] = trim($m[1]);

                continue;
            }

            // Otherwise treat as the organization / supplementary line.
            // First such line wins; subsequent lines are appended.
            $clean = $this->stripBilingualTail($body);
            $card['organization'] = $card['organization']
                ? $card['organization']."\n".$clean
                : $clean;
        }

        return $card;
    }

    /**
     * Parse the "Issued by …" footer block into one card per ### heading.
     *
     * @return array<int, array{role_en: string, role_bn: ?string, ...}>
     */
    public function parseFooter(string $markdown): array
    {
        // Strip the leading paragraph (the bilingual attribution line above the H3s).
        if (! preg_match_all('/^### (.+?)$\n(.*?)(?=^### |\z)/smu', $markdown, $matches, PREG_SET_ORDER)) {
            return [];
        }

        $cards = [];
        foreach ($matches as $m) {
            $heading = trim($m[1]);
            $body = trim($m[2]);
            [$roleEn, $roleBn] = $this->splitBilingualHeading($heading);

            $cards[] = array_merge(
                ['role_en' => $roleEn, 'role_bn' => $roleBn],
                $this->parseBullets($body),
            );
        }

        return $cards;
    }

    /** Drops the " / BengaliText" tail used as inline glosses on the source. */
    private function stripBilingualTail(string $text): string
    {
        return preg_replace('/\s*\/\s*\S.*$/u', '', $text) ?? $text;
    }

    /** @return array{0: string, 1: ?string} */
    private function splitBilingualHeading(string $heading): array
    {
        if (str_contains($heading, ' / ')) {
            [$en, $bn] = explode(' / ', $heading, 2);

            return [trim($en), trim($bn)];
        }

        return [trim($heading), null];
    }
}
