<?php

namespace App\Http\Requests\Cms;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCmsUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.manage');
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'role' => ['required', Rule::in([User::ROLE_ADMIN, User::ROLE_LECTURER, User::ROLE_STUDENT])],
            'status' => ['nullable', Rule::in(['active', 'suspended', 'deactivated'])],
            'locale' => ['nullable', Rule::in(config('cms.locales', ['en']))],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
        ];
    }
}
