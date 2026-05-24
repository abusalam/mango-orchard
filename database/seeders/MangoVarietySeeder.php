<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\MangoVariety;
use Illuminate\Database\Seeder;

class MangoVarietySeeder extends Seeder
{
    public function run(): void
    {
        $varieties = [
            ['Alphonso',        'Ratnagiri, India',     'Apr – Jun', 4, 6, 'Saffron flesh, dense and creamy with a perfumed honey-apricot sweetness. Often called the king of mangoes.', ['Premium', 'Aromatic', 'Low fiber'], 'sunrise'],
            ['Kesar',           'Gujarat, India',       'May – Jul', 5, 7, 'Bright saffron pulp with floral honey notes and a clean, citrus-lifted finish. The pride of Junagadh.',     ['Aromatic', 'Pulp'],                'amber'],
            ['Ataulfo',         'Chiapas, Mexico',      'Mar – Jul', 3, 7, 'Buttery, custard-soft flesh tasting of honey and ripe pineapple. Almost no fiber.',                        ['Buttery', 'Fiberless'],            'honey'],
            ['Tommy Atkins',    'Florida, USA',         'Mar – Jul', 3, 7, 'Firm and mildly sweet with a hint of tartness. The supermarket workhorse — long shelf life, vivid blush.', ['Common', 'Firm'],                  'lime'],
            ['Haden',           'Florida, USA',         'Mar – Apr', 3, 4, 'Aromatic, full-flavored sweet-tart balance with a slight peach note. The parent of many modern hybrids.',  ['Heritage', 'Sweet-tart'],          'rose'],
            ['Keitt',           'Florida / Mexico',     'Aug – Oct', 8, 10,'Late-season, juicy, lemon-bright sweetness. Stays green even when ripe — squeeze to check.',                ['Late-season', 'Juicy'],            'emerald'],
            ['Kent',            'Mexico / Ecuador',     'Jun – Aug', 6, 8, 'Sweet, richly aromatic, almost dripping when ripe. Minimal fiber — ideal for slicing and smoothies.',      ['Juicy', 'Versatile'],              'kent'],
            ['Carabao',         'Philippines',          'Apr – Jun', 4, 6, 'Slender and golden with an intense, tangy-sweet tropical punch. The national fruit of the Philippines.',  ['Tangy', 'National fruit'],         'carabao'],
            ['Chaunsa',         'Multan, Pakistan',     'Jun – Sep', 6, 9, 'Intensely fragrant, dripping with sugar-honey juice and almost no fiber. Eaten by sucking, not slicing.',  ['Premium', 'Juicy'],                'amber'],
            ['Langra',          'Varanasi, India',      'Jul – Aug', 7, 8, 'Stays green-skinned when ripe. Spicy-sweet, lemony, with a unique turpentine-citrus aroma some adore.',    ['Heirloom', 'Aromatic'],            'green'],
            ['Dasheri',         'Malihabad, India',     'Jun – Jul', 6, 7, 'Long, slender, mellow-sweet with a soft melon-like aroma. The backbone of North Indian mango summers.',    ['Mellow', 'Eat-out-of-hand'],       'dasheri'],
            ['Nam Dok Mai',     'Thailand',             'Apr – Jun', 4, 6, 'Slim, golden, silky-smooth flesh with pure honey sweetness. The classic for sticky rice with mango.',      ['Silky', 'Dessert'],                'honey'],
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
