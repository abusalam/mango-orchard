<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOnboardingProfileRequest extends FormRequest
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
        ];
    }
}
