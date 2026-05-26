<?php

namespace Database\Factories;

use App\Models\User;
use App\Roles;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            'region' => fake()->city(),
            'expertise' => fake()->randomElement(array_keys(User::EXPERTISE_LEVELS)),
            'notify_seasonal' => true,
            'subscribe_newsletter' => false,
            'onboarding_completed_at' => now(),
        ];
    }

    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function unonboarded(): static
    {
        return $this->state(fn (array $attributes) => [
            'region' => null,
            'expertise' => null,
            'favorite_variety_id' => null,
            'notify_seasonal' => false,
            'subscribe_newsletter' => false,
            'onboarding_completed_at' => null,
        ]);
    }

    public function superuser(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::SUPERUSER));
    }

    public function curator(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::CURATOR));
    }

    public function grower(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::GROWER));
    }

    public function impersonator(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::IMPERSONATOR));
    }

    public function convener(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::CONVENER));
    }

    public function advisor(): static
    {
        return $this->afterCreating(fn (User $user) => $user->assignRole(Roles::ADVISOR));
    }
}
