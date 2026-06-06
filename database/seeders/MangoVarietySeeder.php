<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Modules\MangoOrchard\Models\MangoVariety;
use Illuminate\Database\Seeder;

/**
 * Twelve varieties native to or widely grown in Malda district, West Bengal —
 * the country's most famous mango belt. Several (Himsagar, Fazli, Lakshmanbhog,
 * Langra) hold GI tags and are the backbone of the district's spring/summer
 * trade. Hybrids (Amrapali, Mallika) and southern late-bearers (Neelam)
 * round out the orchard year from May through September.
 */
class MangoVarietySeeder extends Seeder
{
    public function run(): void
    {
        $varieties = [
            ['Himsagar',     'English Bazar, Malda',     'Late May – Jun', 5, 6,  'GI-tagged "king of Malda". Saffron-orange flesh, fiber-free, juice that drips down the wrist. The benchmark every mango in the district is measured against.', ['GI-tagged', 'Premium', 'Fiberless'], 'amber'],
            ['Langra',       'Old Malda, West Bengal',   'Jul – Aug',      7, 8,  'Stays grass-green even when ripe — squeeze gently to test. Spicy-sweet pulp with a turpentine top note that mango aficionados prize. GI-tagged Malda heirloom.',  ['GI-tagged', 'Heirloom', 'Aromatic'], 'green'],
            ['Fazli',        'Kaliachak, Malda',          'Jul – Aug',      7, 8,  'Giant late-season fruit, often over a kilo each. Tangy-sweet, mostly destined for pickles, chutneys and aam papad. The district\'s third GI-tagged variety.',     ['GI-tagged', 'Late-season', 'Pickling'], 'kent'],
            ['Lakshmanbhog', 'English Bazar, Malda',     'Late May – Jun', 5, 6,  'Long, elegant, honey-sweet with a touch of citrus. Slim-stoned and dessert-friendly. Recently added to West Bengal\'s GI register alongside Himsagar and Fazli.',  ['GI-tagged', 'Dessert'],              'sunrise'],
            ['Gopalbhog',    'Habibpur, Malda',           'May – Jun',      5, 6,  'One of the earliest varieties out of the Malda orchards. Smaller, intensely fragrant, almost confectionery-sweet — the season-opener locals wait for.',          ['Early-season', 'Aromatic'],           'honey'],
            ['Kishan Bhog',  'Bamangola, Malda',          'Jun',            6, 6,  'Round, rose-blushed skin and dense honey-tart pulp. A favourite at home tables across North Bengal, sliced and salted as a quick mid-day snack.',                    ['Mid-season', 'Juicy'],                'rose'],
            ['Amrapali',     'Malda (IARI hybrid)',       'Jul – Aug',      7, 8,  'Dwarf hybrid (Dasheri × Neelam) bred at IARI for high-density planting. Now widespread across Malda smallholders — reliable yields, deep red-orange flesh.',            ['Hybrid', 'High-yield'],              'amber'],
            ['Mallika',      'Malda (IARI hybrid)',       'Jul – Aug',      7, 8,  'IARI hybrid (Neelam × Dasheri) prized for thick, fiber-free pulp and a long shelf life. The go-to choice for pulp processors and exporters in the district.',         ['Hybrid', 'Export-grade'],            'kent'],
            ['Bombai',       'Manikchak, Malda',          'May',            5, 5,  'A very early variety with a hint of green even at peak. Tangy-forward, less sweet than Himsagar, used both fresh and for green-mango drinks called aam pora sharbat.', ['Early-season', 'Tangy'],             'lime'],
            ['Ashwina',      'Ratua, Malda',              'Aug – Sep',      8, 9,  'The very last variety of the Malda season — fibrous, sharp, never eaten raw. The whole August harvest goes into pickles, chutneys and dried-mango papad.',           ['Late-season', 'Pickling'],           'dasheri'],
            ['Mohanbhog',    'Chanchal, Malda',           'Jun – Jul',      6, 7,  'Slender, golden, with a delicate floral aroma. A connoisseur\'s variety — small volumes, sold locally rather than packed for export.',                                ['Mid-season', 'Heirloom'],            'carabao'],
            ['Neelam',       'Malda (southern hybrid)',   'Jul – Aug',      7, 8,  'Late-bearing southern variety widely interplanted across Malda for its dependable crop even in off-years. Small fruit, deep red blush, mellow-sweet.',                    ['Late-season', 'Reliable'],           'sunrise'],
        ];

        foreach ($varieties as [$name, $origin, $season, $start, $end, $flavor, $tags, $theme]) {
            MangoVariety::updateOrCreate(
                ['name' => $name],
                [
                    'origin' => $origin,
                    'season' => $season,
                    'season_start' => $start,
                    'season_end' => $end,
                    'flavor' => $flavor,
                    'tags' => $tags,
                    'theme' => $theme,
                ],
            );
        }
    }
}
