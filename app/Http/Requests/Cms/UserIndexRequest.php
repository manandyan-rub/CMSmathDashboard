<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UserIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.view');
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'role' => ['nullable', 'in:student,lecturer,admin'],
            'status' => ['nullable', 'in:active,suspended,deactivated'],
            'country_id' => ['nullable', 'integer', 'exists:countries,id'],
            'locale' => ['nullable', 'in:'.implode(',', config('cms.locales', ['en']))],
            'registered_from' => ['nullable', 'date'],
            'registered_to' => ['nullable', 'date', 'after_or_equal:registered_from'],
            'last_active_from' => ['nullable', 'date'],
            'verified' => ['nullable', 'boolean'],
            'sort' => ['nullable', 'in:name,email,role,status,last_active_at,created_at'],
            'direction' => ['nullable', 'in:asc,desc'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ];
    }
}
