<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Permissions;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can(Permissions::ROLES_MANAGE) ?? false;
    }

    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');

        return [
            'name' => [
                'required',
                'string',
                'max:60',
                'regex:/^[a-z0-9._-]+$/i',
                Rule::unique('roles', 'name')->ignore($role->id),
            ],
            'permissions' => ['array'],
            'permissions.*' => ['string', Rule::in(array_keys(Permissions::ALL))],
        ];
    }
}
