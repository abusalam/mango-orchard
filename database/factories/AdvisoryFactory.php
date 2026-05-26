<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Advisory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Advisory>
 */
class AdvisoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(6),
            'body' => $this->faker->paragraphs(2, asText: true),
            'category' => $this->faker->randomElement(array_keys(Advisory::CATEGORIES)),
            'severity' => Advisory::SEVERITY_INFO,
            'issued_by' => User::factory(),
            'issued_at' => now(),
            'expires_at' => null,
            'published' => true,
        ];
    }

    public function draft(): static
    {
        return $this->state(fn () => ['published' => false]);
    }

    public function urgent(): static
    {
        return $this->state(fn () => ['severity' => Advisory::SEVERITY_URGENT]);
    }

    public function warning(): static
    {
        return $this->state(fn () => ['severity' => Advisory::SEVERITY_WARNING]);
    }

    public function expired(): static
    {
        return $this->state(fn () => [
            'issued_at' => now()->subDays(30),
            'expires_at' => now()->subDay(),
        ]);
    }

    public function seasonal(): static
    {
        return $this->state(fn () => ['category' => Advisory::CATEGORY_SEASONAL]);
    }

    public function bestPractice(): static
    {
        return $this->state(fn () => ['category' => Advisory::CATEGORY_BEST_PRACTICE]);
    }

    public function pestAlert(): static
    {
        return $this->state(fn () => ['category' => Advisory::CATEGORY_PEST_ALERT]);
    }
}
