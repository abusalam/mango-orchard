<?php

declare(strict_types=1);

namespace Database\Factories\SchemeMonitoring;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Scheme;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    public function definition(): array
    {
        return [
            'scheme_id' => Scheme::factory(),
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraph(),
            'deadline' => $this->faker->dateTimeBetween('+1 day', '+30 days'),
            'status' => Task::STATUS_PENDING,
            'priority' => Task::PRIORITY_NORMAL,
            'assigned_to' => User::factory(),
            'created_by' => User::factory(),
        ];
    }

    public function dueIn(int $days): static
    {
        return $this->state(fn () => [
            'deadline' => now()->addDays($days)->toDateString(),
        ]);
    }

    public function overdueBy(int $days): static
    {
        return $this->state(fn () => [
            'deadline' => now()->subDays($days)->toDateString(),
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn () => [
            'status' => Task::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }
}
