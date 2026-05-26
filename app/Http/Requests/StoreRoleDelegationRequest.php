<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\RoleDelegation;
use App\Models\User;
use App\Roles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Spatie\Permission\Models\Role;

class StoreRoleDelegationRequest extends FormRequest
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
                Rule::exists('roles', 'id')->where(fn ($q) => $q->whereIn('name', Roles::delegatable())),
            ],
            'recipient_email' => ['required', 'email', 'exists:users,email'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $delegator = $this->user();
            $roleId = (int) $this->input('role_id');
            $recipientEmail = (string) $this->input('recipient_email');

            $role = Role::find($roleId);
            $recipient = User::firstWhere('email', $recipientEmail);

            if ($role === null || $recipient === null) {
                // The individual field validators already flag these.
                return;
            }

            if (! $delegator->hasRole($role->name)) {
                $validator->errors()->add('role_id', "You don't hold the {$role->name} role, so you can't delegate it.");

                return;
            }

            if ($recipient->id === $delegator->id) {
                $validator->errors()->add('recipient_email', "You can't delegate a role to yourself.");

                return;
            }

            if ($recipient->hasRole($role->name)) {
                $validator->errors()->add('recipient_email', "{$recipient->name} already holds the {$role->name} role.");

                return;
            }

            $activeExists = RoleDelegation::active()
                ->where('user_id', $recipient->id)
                ->where('role_id', $role->id)
                ->exists();

            if ($activeExists) {
                $validator->errors()->add('recipient_email', "An active delegation of the {$role->name} role to {$recipient->name} already exists.");
            }
        });
    }
}
