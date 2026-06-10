<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\GalleryAlbum;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;

class GallerySeeder extends Seeder
{
    public function run(): void
    {
        $albums = [
            ['slug' => 'orchards-of-malda', 'title' => 'Orchards of Malda', 'description' => 'Landscapes of mango orchards across the district through the seasons.'],
            ['slug' => 'harvest-2026', 'title' => 'Harvest 2026', 'description' => 'Field photography from the 2026 mango harvest — growers, baskets, and trucks loading.'],
            ['slug' => 'pack-houses', 'title' => 'Pack-Houses & Sorting Lines', 'description' => 'Inside the approved pack-houses where fruit is graded, washed, and crated for export.'],
            ['slug' => 'mango-fair', 'title' => 'Annual Mango Fair', 'description' => 'Stalls, judging, and cultural events at the annual Malda mango fair.'],
        ];

        Telemetry::withoutRecording(function () use ($albums): void {
            foreach ($albums as $i => $payload) {
                GalleryAlbum::query()->firstOrCreate(
                    ['slug' => $payload['slug']],
                    array_merge($payload, [
                        'display_order' => $i + 1,
                        'published' => true,
                    ]),
                );
            }
        });
    }
}
