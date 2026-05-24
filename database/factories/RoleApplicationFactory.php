<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RoleApplication;
use App\Models\User;
use App\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<RoleApplication>
 */
class RoleApplicationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => fn () => Role::findByName(Roles::GROWER)->id,
            'message' => $this->faker->optional()->sentence(),
            'status' => RoleApplication::STATUS_PENDING,
        ];
    }

    public function approved(): static
    {
        return $this->state(fn () => [
            'status' => RoleApplication::STATUS_APPROVED,
            'reviewed_at' => now(),
        ]);
    }

    public function rejected(): static
    {
        return $this->state(fn () => [
            'status' => RoleApplication::STATUS_REJECTED,
            'reviewed_at' => now(),
        ]);
    }
}
