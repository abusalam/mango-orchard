<?php

declare(strict_types=1);

namespace Database\Factories\SchemeMonitoring;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Scheme;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Scheme>
 */
class SchemeFactory extends Factory
{
    protected $model = Scheme::class;

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('-30 days', '+30 days');

        return [
            'name' => $this->faker->unique()->catchPhrase(),
            'description' => $this->faker->paragraph(),
            'start_date' => $start,
            'end_date' => $this->faker->dateTimeBetween($start, '+180 days'),
            'owner_id' => User::factory(),
            'status' => Scheme::STATUS_ACTIVE,
        ];
    }
}
