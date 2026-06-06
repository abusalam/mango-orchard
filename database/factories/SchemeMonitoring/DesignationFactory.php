<?php

declare(strict_types=1);

namespace Database\Factories\SchemeMonitoring;

use App\Modules\SchemeMonitoring\Models\Designation;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Designation>
 */
class DesignationFactory extends Factory
{
    protected $model = Designation::class;

    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->jobTitle(),
            'description' => $this->faker->sentence(),
            'level' => $this->faker->numberBetween(0, 10),
        ];
    }
}
