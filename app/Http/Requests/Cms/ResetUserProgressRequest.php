<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class ResetUserProgressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.progress.reset');
    }

    public function rules(): array
    {
        return [
            'level_id' => ['nullable', 'integer', 'exists:math_levels,id'],
            'confirmed' => ['accepted'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
