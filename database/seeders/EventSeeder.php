<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Event;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;

class EventSeeder extends Seeder
{
    public function run(): void
    {
        Telemetry::withoutRecording(function (): void {
            $events = [
                [
                    'title' => 'Pre-monsoon Pruning & Canopy Management',
                    'description' => "A hands-on field day at a working Alphonso orchard. Cover canopy shaping, dead-wood removal, and structuring for next season's flowering. Bring secateurs and shoes you don't mind dirtying.\n\nIdeal for: orchard owners with 50+ trees who manage their own crew.",
                    'start_at' => now()->addDays(12)->setTime(9, 30),
                    'end_at' => now()->addDays(12)->setTime(13, 0),
                    'location' => 'Devgad, Maharashtra',
                    'location_url' => 'https://maps.google.com/?q=Devgad+Maharashtra',
                    'host' => 'Konkan Krishi Vidyapeeth (Dapoli)',
                    'capacity' => 40,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Integrated Pest Management — Fruit Fly & Mealybug Control',
                    'description' => "Half-day seminar on monitoring traps, neem-based sprays, sticky bands, and sanitation routines. Compare conventional and organic approaches with cost breakdowns.\n\nLunch provided. Q&A with a state extension officer.",
                    'start_at' => now()->addDays(28)->setTime(10, 0),
                    'end_at' => now()->addDays(28)->setTime(15, 30),
                    'location' => 'KVK Ratnagiri',
                    'location_url' => null,
                    'host' => 'KVK Ratnagiri',
                    'capacity' => 75,
                    'registration_url' => 'https://example.com/register/ipm-ratnagiri',
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Post-harvest Handling, Ripening & Cold-chain Basics',
                    'description' => 'From plucking maturity indices to ethylene ripening chambers — what every grower selling B2B needs to know. Includes a tour of a CFC cold-storage facility.',
                    'start_at' => now()->addMonths(2)->setTime(9, 0),
                    'end_at' => now()->addMonths(2)->setTime(17, 0),
                    'location' => 'Pune, Maharashtra',
                    'location_url' => null,
                    'host' => 'Maharashtra State Agricultural Marketing Board',
                    'capacity' => 60,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Online Q&A: Export Grading Standards for Alphonso',
                    'description' => 'A live webinar walking through APEDA grading specifications, common rejection reasons at port, and how small growers can pool consignments. Recording shared with registrants.',
                    'start_at' => now()->addDays(6)->setTime(18, 30),
                    'end_at' => now()->addDays(6)->setTime(20, 0),
                    'location' => 'Online',
                    'location_url' => 'https://example.com/webinar/apeda-grading',
                    'host' => 'Mango Growers Association',
                    'capacity' => null,
                    'registration_url' => 'https://example.com/register/apeda-webinar',
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Organic Certification Walk-through (NPOP)',
                    'description' => "A two-session workshop covering paperwork, inspection prep, and the 3-year conversion period. Bring your land records and last season's input log.",
                    'start_at' => now()->addMonths(3)->setTime(9, 30),
                    'end_at' => now()->addMonths(3)->setTime(16, 0),
                    'location' => 'Bengaluru, Karnataka',
                    'location_url' => null,
                    'host' => 'ICAR – IIHR Bengaluru',
                    'capacity' => 50,
                    'registration_url' => null,
                    'status' => Event::STATUS_PUBLISHED,
                ],
                [
                    'title' => 'Flowering Stage Nutrient Spray Clinic (Spring 2026)',
                    'description' => 'A past clinic on micronutrient sprays during panicle emergence. Notes and slide deck available on request.',
                    'start_at' => now()->subMonths(2)->setTime(10, 0),
                    'end_at' => now()->subMonths(2)->setTime(13, 0),
                    'location' => 'Vengurla, Maharashtra',
                    'location_url' => null,
                    'host' => 'KVK Sindhudurg',
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
