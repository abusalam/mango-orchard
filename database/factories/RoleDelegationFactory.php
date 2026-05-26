<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\RoleDelegation;
use App\Models\User;
use App\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Spatie\Permission\Models\Role;

/**
 * @extends Factory<RoleDelegation>
 */
class RoleDelegationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'role_id' => fn () => Role::findByName(Roles::GROWER)->id,
            'delegated_by' => User::factory(),
            'delegated_at' => now(),
        ];
    }

    public function revoked(): static
    {
        return $this->state(fn () => [
            'revoked_at' => now(),
            'revoke_reason' => $this->faker->sentence(),
        ]);
    }
}
