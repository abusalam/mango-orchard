<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'region' => ['required', 'string', 'max:120'],
            'expertise' => ['required', 'string', Rule::in(array_keys(User::EXPERTISE_LEVELS))],
            'favorite_variety_id' => ['nullable', Rule::exists('mango_varieties', 'id')],
            'notify_seasonal' => ['nullable', 'boolean'],
            'subscribe_newsletter' => ['nullable', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'notify_seasonal' => $this->boolean('notify_seasonal'),
            'subscribe_newsletter' => $this->boolean('subscribe_newsletter'),
        ]);
    }
}
