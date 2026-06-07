<?php

declare(strict_types=1);

namespace Database\Factories\SchemeMonitoring;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\MonitorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MonitorProfile>
 */
class MonitorProfileFactory extends Factory
{
    protected $model = MonitorProfile::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
        ];
    }
}
