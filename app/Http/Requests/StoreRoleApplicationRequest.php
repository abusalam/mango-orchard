<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RoleApplication;
use App\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class StoreRoleApplicationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($q) => $q->whereNotIn('name', Roles::nonApplicable())),
            ],
            'message' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $roleId = (int) $this->input('role_id');
            if ($roleId === 0) {
                return;
            }

            $user = $this->user();
            $role = Role::find($roleId);
            if ($role === null) {
                return;
            }

            if ($user->hasRole($role->name)) {
                $validator->errors()->add('role_id', "You already hold the {$role->name} role.");

                return;
            }

            $hasPending = $user->roleApplications()
                ->where('role_id', $role->id)
                ->where('status', RoleApplication::STATUS_PENDING)
                ->exists();

            if ($hasPending) {
                $validator->errors()->add('role_id', "You already have a pending application for the {$role->name} role.");
            }
        });
    }
}
