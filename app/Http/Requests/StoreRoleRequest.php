<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::ROLES_MANAGE) ?? false;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9._-]+$/i',
                Rule::unique('roles', 'name'),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::ALL))],
        ];
    }
}
