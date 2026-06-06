<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\MangoOrchard\Models\Event;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;

/**
 * Training, extension and trade events drawn from the institutions that
 * actually serve Malda's mango economy: KVK Malda (ICAR), Uttar Banga
 * Krishi Viswavidyalaya, BCKV Mohanpur, the district horticulture office,
 * and the Malda Mango Growers' Association.
 */
class EventSeeder extends Seeder
{
    public function run(): void
    {
        Telemetry::withoutRecording(function (): void {
            $events = [
                [
                    'title' => 'Pre-monsoon pruning & canopy management — Himsagar orchards',
                    'description' => "A hands-on field day in a working Himsagar block at the Mango Research Sub-Station. Cover canopy shaping, dead-wood removal, and structuring for next season's flowering. Bring secateurs and shoes you don't mind dirtying.\n\nIdeal for: orchard owners with 50+ trees who manage their own crew.",
                    'start_at' => now()->addDays(12)->setTime(9, 30),
                    'end_at' => now()->addDays(12)->setTime(13, 0),
                    'location' => 'Mango Research Sub-Station, Mothabari, Malda',
                    'location_url' => 'https://maps.google.com/?q=Mothabari+Malda',
                    'host' => 'Uttar Banga Krishi Viswavidyalaya (UBKV)',
                    'capacity' => 40,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'IPM clinic — mango hopper, fruit fly & anthracnose',
                    'description' => "Half-day seminar at KVK Malda on monitoring traps, neem-based sprays, sticky bands and sanitation routines. Compare conventional and organic approaches with cost breakdowns calibrated for North Bengal orchards.\n\nLunch provided. Q&A with West Bengal state extension officers.",
                    'start_at' => now()->addDays(28)->setTime(10, 0),
                    'end_at' => now()->addDays(28)->setTime(15, 30),
                    'location' => 'KVK Malda, Ratua',
                    'location_url' => null,
                    'host' => 'Krishi Vigyan Kendra Malda (ICAR)',
                    'capacity' => 75,
                    'registration_url' => 'https://example.com/register/ipm-kvk-malda',
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Post-harvest handling, ripening & cold-chain basics',
                    'description' => 'From plucking maturity indices to ethylene ripening chambers — what every grower selling B2B needs to know. Includes a tour of the Malda Cold Storage cooperative facility at Kaliachak.',
                    'start_at' => now()->addMonths(2)->setTime(9, 0),
                    'end_at' => now()->addMonths(2)->setTime(17, 0),
                    'location' => 'Malda Cold Storage Co-op, Kaliachak',
                    'location_url' => null,
                    'host' => 'West Bengal State Agricultural Marketing Board',
                    'capacity' => 60,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Online Q&A — APEDA export grading for GI varieties (Himsagar, Langra, Fazli)',
                    'description' => "A live webinar walking through APEDA grading specifications, common rejection reasons at Kolkata port, and how Malda smallholders can pool consignments through producer companies. Recording shared with registrants.",
                    'start_at' => now()->addDays(6)->setTime(18, 30),
                    'end_at' => now()->addDays(6)->setTime(20, 0),
                    'location' => 'Online',
                    'location_url' => 'https://example.com/webinar/apeda-malda-gi',
                    'host' => 'Malda Mango Growers\' Association',
                    'capacity' => null,
                    'registration_url' => 'https://example.com/register/apeda-malda-webinar',
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Organic certification walk-through (NPOP) for North Bengal orchards',
                    'description' => "Two-session workshop at BCKV's regional centre covering paperwork, inspection prep, and the 3-year conversion period. Bring your land records and last season's input log. Translation between English and Bengali on request.",
                    'start_at' => now()->addMonths(3)->setTime(9, 30),
                    'end_at' => now()->addMonths(3)->setTime(16, 0),
                    'location' => 'BCKV Regional Research Station, Pundibari',
                    'location_url' => null,
                    'host' => 'Bidhan Chandra Krishi Viswavidyalaya (BCKV)',
                    'capacity' => 50,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Flowering-stage micronutrient spray clinic — Spring 2026',
                    'description' => 'A past clinic on micronutrient sprays during panicle emergence in Himsagar and Lakshmanbhog blocks. Notes and slide deck still available on request from the District Horticulture Office.',
                    'start_at' => now()->subMonths(2)->setTime(10, 0),
                    'end_at' => now()->subMonths(2)->setTime(13, 0),
                    'location' => 'District Horticulture Office, English Bazar, Malda',
                    'location_url' => null,
                    'host' => 'Department of Food Processing Industries & Horticulture, West Bengal',
                    'capacity' => 35,
                    'registration_url' => null,
                    'status' => Event::STATUS_COMPLETED,
                ],
            ];

            foreach ($events as $attributes) {
                Event::create($attributes);
            }
        });
    }
}
