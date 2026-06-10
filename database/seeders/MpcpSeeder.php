<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Mpcp\MarkdownParser;
use App\Models\MpcpDocument;
use App\Models\MpcpEntry;
use App\Models\MpcpSection;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;
use RuntimeException;

class MpcpSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('seeders/data/mpcp.md');

        if (! is_file($path)) {
            throw new RuntimeException("MPCP markdown source not found at {$path}");
        }

        $parsed = (new MarkdownParser)->parse(file_get_contents($path));

        Telemetry::withoutRecording(function () use ($parsed): void {
            // Document — single row, replaced in full so re-seeding picks up
            // copy edits to the source markdown.
            MpcpDocument::query()->updateOrCreate(
                ['id' => 1],
                $parsed['document'],
            );

            foreach ($parsed['sections'] as $payload) {
                $entries = $payload['entries'];
                unset($payload['entries']);

                $section = MpcpSection::query()->updateOrCreate(
                    ['slug' => $payload['slug']],
                    array_merge($payload, ['published' => true]),
                );

                // Clear + rebuild entries each run so the seed matches the
                // markdown 1:1. Admin-side CRUD will use updateOrCreate per
                // entry, not delete-and-rebuild; only this seeder wipes.
                MpcpEntry::query()->where('mpcp_section_id', $section->id)->delete();

                foreach ($entries as $entry) {
                    MpcpEntry::query()->create([
                        'mpcp_section_id' => $section->id,
                        'data' => $entry['data'],
                        'position' => $entry['position'] ?? 0,
                    ]);
                }
            }
        });
    }
}
