<?php

declare(strict_types=1);

namespace Database\Factories\SchemeMonitoring;

use App\Models\User;
use App\Modules\SchemeMonitoring\Models\Attachment;
use App\Modules\SchemeMonitoring\Models\Task;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    protected $model = Attachment::class;

    public function definition(): array
    {
        $name = $this->faker->word().'.pdf';

        return [
            // Defaults to a task attachment — tests can override via state.
            'attachable_type' => (new Task)->getMorphClass(),
            'attachable_id' => Task::factory(),
            'uploaded_by' => User::factory(),
            'original_name' => $name,
            'path' => 'monitoring-attachments/'.$this->faker->uuid().'-'.$name,
            'mime_type' => 'application/pdf',
            'size_bytes' => $this->faker->numberBetween(1024, 5_000_000),
        ];
    }
}
