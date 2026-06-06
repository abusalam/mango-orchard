<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Modules\MangoOrchard\Models\Event;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Event>
 */
class EventFactory extends Factory
{
    protected $model = Event::class;

    private const array SAMPLE_TITLES = [
        'Pre-monsoon Pruning Workshop',
        'Integrated Pest Management for Hapus',
        'Soil Health & Drip Irrigation Clinic',
        'Post-harvest Handling & Cold-chain Basics',
        'Export Grading Standards for Alphonso',
        'Organic Certification Walk-through',
        'Bagging Techniques to Reduce Fruit-fly Damage',
        'Flowering Stage Nutrient Management',
        'GI Tag & Direct-to-Consumer Marketing',
        'Climate Resilient Mango Cultivation',
    ];

    private const array SAMPLE_HOSTS = [
        'Konkan Krishi Vidyapeeth',
        'ICAR – IIHR Bengaluru',
        'Maharashtra State Horticulture Dept',
        'Mango Growers Association',
        'KVK Ratnagiri',
    ];

    public function definition(): array
    {
        $start = $this->faker->dateTimeBetween('+1 week', '+3 months');
        $end = (clone $start)->modify('+'.$this->faker->numberBetween(2, 8).' hours');

        return [
            'title' => $this->faker->randomElement(self::SAMPLE_TITLES),
            'description' => $this->faker->paragraphs(3, true),
            'start_at' => $start,
            'end_at' => $end,
            'location' => $this->faker->city().', '.$this->faker->randomElement(['Maharashtra', 'Karnataka', 'Gujarat', 'Andhra Pradesh', 'Tamil Nadu']),
            'location_url' => null,
            'host' => $this->faker->randomElement(self::SAMPLE_HOSTS),
            'capacity' => $this->faker->randomElement([null, 30, 50, 75, 100, 150]),
            'registration_url' => null,
            'status' => Event::STATUS_PUBLISHED,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_DRAFT]);
    }

    public function cancelled(): static
    {
        return $this->state(fn () => ['status' => Event::STATUS_CANCELLED]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => Event::STATUS_COMPLETED,
            'start_at' => $this->faker->dateTimeBetween('-6 months', '-1 week'),
            'end_at' => null,
        ]);
    }

    public function online(): static
    {
        return $this->state(fn () => [
            'location' => 'Online',
            'location_url' => 'https://example.com/join/'.$this->faker->slug(2),
        ]);
    }
}
