<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\MangoVariety;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<MangoVariety>
 */
class MangoVarietyFactory extends Factory
{
    public function definition(): array
    {
        $name = $this->faker->unique()->words(2, true).' Mango';
        $start = $this->faker->numberBetween(1, 10);
        $end = $this->faker->numberBetween($start, 12);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name),
            'origin' => $this->faker->city().', '.$this->faker->country(),
            'season' => sprintf('%s – %s', $this->monthAbbr($start), $this->monthAbbr($end)),
            'season_start' => $start,
            'season_end' => $end,
            'flavor' => $this->faker->sentence(12),
            'tags' => $this->faker->randomElements(
                ['Premium', 'Aromatic', 'Juicy', 'Fiberless', 'Heirloom', 'Tangy', 'Buttery', 'Silky'],
                $this->faker->numberBetween(1, 3),
            ),
            'theme' => $this->faker->randomElement(array_keys(MangoVariety::THEMES)),
        ];
    }

    private function monthAbbr(int $month): string
    {
        return ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'][$month - 1];
    }
}
