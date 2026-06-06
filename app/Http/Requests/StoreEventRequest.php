<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Modules\MangoOrchard\Models\Event;
use App\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEventRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::EVENTS_MANAGE) ?? false;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:5000'],
            'start_at' => ['required', 'date'],
            'end_at' => ['nullable', 'date', 'after_or_equal:start_at'],
            'location' => ['required', 'string', 'max:180'],
            'location_url' => ['nullable', 'url', 'max:500'],
            'host' => ['nullable', 'string', 'max:180'],
            'capacity' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'registration_url' => ['nullable', 'url', 'max:500'],
            'status' => ['required', Rule::in(array_keys(Event::STATUSES))],
        ];
    }
}
