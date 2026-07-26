<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UserAdminActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.manage');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:activate,suspend,deactivate,verify_email,change_role,send_password_reset'],
            'role' => ['required_if:action,change_role', 'nullable', 'in:student,lecturer,admin'],
            'reason' => ['required_unless:action,send_password_reset', 'nullable', 'string', 'min:3', 'max:2000'],
        ];
    }
}
