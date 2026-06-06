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
        $applicable = Roles::applicableTo($this->user());

        return [
            'role_id' => [
                'required',
                'integer',
                Rule::exists('roles', 'id')->where(fn ($q) => $q->whereIn('name', $applicable)),
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

            // Module sub-roles are only self-applicable once the user has
            // been enrolled in the module by an admin. Pretty-print which
            // membership they're missing so the error explains the gate.
            $module = Roles::moduleFor($role->name);
            if ($module !== null) {
                $membership = Roles::modules()[$module]['membership'];
                if ($role->name !== $membership && ! $user->hasRole($membership)) {
                    $validator->errors()->add('role_id', "Applying for the {$role->name} role requires module access; ask an administrator to add you to the {$module} module first.");

                    return;
                }
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
