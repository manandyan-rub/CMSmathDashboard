<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserPermissionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.roles.manage');
    }

    public function rules(): array
    {
        return [
            'permissions' => ['required', 'array'],
            'permissions.*.key' => ['required', 'string', 'exists:permissions,key'],
            'permissions.*.allowed' => ['present', 'nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
