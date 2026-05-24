<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingPreferencesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
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
