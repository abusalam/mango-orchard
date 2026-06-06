<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\MangoOrchard\Models\Advisory;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;
use App\Roles;
use App\Telemetry\Telemetry;
use Illuminate\Database\Seeder;

/**
 * A handful of orchard advisories calibrated for Malda district: pest alerts
 * matching the North Bengal season, GI-variety harvest windows, and broadly
 * applicable best practices. Each pulls its target varieties by name from
 * MangoVarietySeeder — run that first.
 */
class AdvisorySeeder extends Seeder
{
    public function run(): void
    {
        // Need at least one issuer for the advisories to attribute to. If no
        // advisor / superuser exists yet (fresh install), seed a synthetic one
        // so the dashboard + show page can render attribution.
        $issuer = User::role(Roles::ADVISOR)->orderBy('id')->first()
            ?? User::role(Roles::SUPERUSER)->orderBy('id')->first()
            ?? null;

        $byVariety = fn (array $names) => MangoVariety::query()
            ->whereIn('name', $names)
            ->pluck('id')
            ->all();

        Telemetry::withoutRecording(function () use ($issuer, $byVariety): void {
            $advisories = [
                [
                    'title' => 'Mango hopper outbreak — set yellow sticky traps now',
                    'body' => "Field reports from Ratua and English Bazar show mango hopper (Idioscopus clypealis) populations climbing past the 5-per-panicle action threshold. Without intervention you'll see flower drop and sooty mould on foliage within 10 days.\n\nWhat to do this week:\n• Hang yellow sticky traps at 25–30 per acre, at canopy height\n• If counts stay above threshold, spray imidacloprid (0.5 ml/L) or neem oil (3%) in the cool morning hours\n• Avoid spraying during full bloom — protect your pollinators\n\nReport sightings to KVK Malda extension on the ICAR helpline.",
                    'category' => Advisory::CATEGORY_PEST_ALERT,
                    'severity' => Advisory::SEVERITY_URGENT,
                    'issued_at' => now()->subDays(2),
                    'expires_at' => now()->addDays(21),
                    'published' => true,
                    'varieties' => $byVariety(['Himsagar', 'Langra', 'Lakshmanbhog', 'Fazli', 'Gopalbhog']),
                ],
                [
                    'title' => 'Pre-monsoon fungicide spray window — anthracnose & powdery mildew',
                    'body' => "With the south-west monsoon expected to make landfall in North Bengal around 10 June, the next ten days are your last reliable spray window before sustained humidity locks in anthracnose (Colletotrichum gloeosporioides) and powdery mildew (Oidium mangiferae).\n\nRecommended programme:\n• Mancozeb 75% WP @ 2 g/L at fruit-pea stage\n• Follow up with hexaconazole 5% EC @ 1 ml/L 14 days later if rain persists\n• Inspect leaf undersides — early powdery mildew shows as white powder along the midrib\n\nThis is general guidance — all varieties benefit, including hybrids.",
                    'category' => Advisory::CATEGORY_SEASONAL,
                    'severity' => Advisory::SEVERITY_WARNING,
                    'issued_at' => now()->subDay(),
                    'expires_at' => now()->addDays(10),
                    'published' => true,
                    'varieties' => [], // general
                ],
                [
                    'title' => 'Fazli harvest window — pluck before the July rains',
                    'body' => "Fazli reaches commercial maturity 110–120 days after flowering. For most Kaliachak orchards that means the last week of July through mid-August. Plucking just before peak softness gives you 7–10 days of shelf life and survives the truck ride to Kolkata wholesale markets.\n\nMaturity cues:\n• Shoulders fill out level with the stem end\n• Skin turns from deep green to olive with yellow undertones\n• A picked fruit, kept at 20–22 °C, fully ripens in 5–7 days\n\nUse padded crates — Fazli's size makes it the most bruise-prone variety in the district.",
                    'category' => Advisory::CATEGORY_SEASONAL,
                    'severity' => Advisory::SEVERITY_INFO,
                    'issued_at' => now()->subDays(5),
                    'expires_at' => now()->addDays(40),
                    'published' => true,
                    'varieties' => $byVariety(['Fazli']),
                ],
                [
                    'title' => 'Fruit-bagging for GI-grade exports — net or paper sleeves',
                    'body' => "Bagging individual fruits at the marble stage (~40 days after fruit-set) is the single highest-ROI practice for export-grade Himsagar, Langra and Lakshmanbhog. It cuts fruit fly damage by 80%+ and lifts your APEDA grading by one full class.\n\nMaterial choices:\n• Two-layer brown paper bags — best for fruit fly, breathable\n• White non-woven polypropylene bags — reusable, slightly more expensive\n• Avoid black bags in summer — sunburn risk\n\nBudget roughly ₹3–5 per bag, recoverable in 2–3 seasons. Cooperative bulk-buy through the Malda Mango Growers' Association keeps cost down.",
                    'category' => Advisory::CATEGORY_BEST_PRACTICE,
                    'severity' => Advisory::SEVERITY_INFO,
                    'issued_at' => now()->subWeek(),
                    'expires_at' => null,
                    'published' => true,
                    'varieties' => $byVariety(['Himsagar', 'Langra', 'Lakshmanbhog', 'Fazli']),
                ],
                [
                    'title' => 'Post-harvest hot-water dip — cuts anthracnose by 70%',
                    'body' => "A 52 °C hot-water dip for 5 minutes immediately after harvest reduces post-harvest anthracnose by 60–75% in trials at BCKV. Costs almost nothing — needs a 200-litre drum, an immersion heater and a thermometer.\n\nProtocol:\n• Water bath stabilised at 52 °C (±0.5)\n• Dip fruit for exactly 5 minutes, then air-dry under shade for 30 minutes before packing\n• Do not exceed 52 °C — fruit develops skin scald above 55 °C\n\nWorks for all soft-fleshed dessert varieties. Best done at the orchard, before transport to the mandi.",
                    'category' => Advisory::CATEGORY_BEST_PRACTICE,
                    'severity' => Advisory::SEVERITY_INFO,
                    'issued_at' => now()->subDays(20),
                    'expires_at' => null,
                    'published' => true,
                    'varieties' => $byVariety(['Himsagar', 'Lakshmanbhog', 'Gopalbhog', 'Amrapali', 'Mallika']),
                ],
                [
                    'title' => 'Early-season Ashwina watch — pulp stone-weevil',
                    'body' => "Pulp stone-weevil (Sternochetus mangiferae) damage in late-bearing Ashwina blocks has been confirmed in three orchards near Ratua. The grub burrows the seed and is invisible until the fruit is cut open — quarantine pests at any export destination.\n\nManagement is preventive only:\n• Strict orchard sanitation — collect and destroy fallen fruit\n• Pheromone traps from panicle emergence onward\n• Do not move infested fruit out of Malda district (legal restriction)",
                    'category' => Advisory::CATEGORY_PEST_ALERT,
                    'severity' => Advisory::SEVERITY_WARNING,
                    'issued_at' => now()->subDays(3),
                    'expires_at' => now()->addDays(60),
                    'published' => true,
                    'varieties' => $byVariety(['Ashwina']),
                ],
            ];

            foreach ($advisories as $attrs) {
                $varietyIds = $attrs['varieties'];
                unset($attrs['varieties']);

                $advisory = Advisory::create([
                    ...$attrs,
                    'issued_by' => $issuer?->id,
                ]);

                if ($varietyIds !== []) {
                    $advisory->varieties()->attach($varietyIds);
                }
            }
        });
    }
}
