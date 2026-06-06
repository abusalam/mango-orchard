<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\MangoOrchard\Models\Listing;
use App\Modules\MangoOrchard\Models\MangoVariety;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Listing>
 */
class ListingFactory extends Factory
{
    protected $model = Listing::class;

    public function definition(): array
    {
        $start = $this->faker->numberBetween(1, 10);
        $end = $this->faker->numberBetween($start, 12);

        return [
            'user_id' => User::factory(),
            'mango_variety_id' => MangoVariety::factory(),
            'farm_name' => $this->faker->company().' Orchards',
            'location' => $this->faker->city().', '.$this->faker->country(),
            'description' => $this->faker->paragraph(2),
            'availability_start_month' => $start,
            'availability_end_month' => $end,
            'price_per_kg' => $this->faker->randomFloat(2, 80, 1200),
            'quantity_available_kg' => $this->faker->numberBetween(50, 5000),
            'contact_email' => $this->faker->companyEmail(),
            'contact_phone' => $this->faker->phoneNumber(),
            'status' => Listing::STATUS_PUBLISHED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Listing::STATUS_DRAFT]);
    }

    public function soldOut(): static
    {
        return $this->state(fn () => ['status' => Listing::STATUS_SOLD_OUT]);
    }
}
