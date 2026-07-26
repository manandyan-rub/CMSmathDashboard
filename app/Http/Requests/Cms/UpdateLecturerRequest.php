<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateLecturerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('lecturers.manage');
    }

    public function rules(): array
    {
        return [
            'approval_status' => ['required', 'in:pending,approved,suspended,rejected'],
            'monthly_ai_limit' => ['required', 'integer', 'min:0', 'max:1000000'],
            'storage_limit_bytes' => ['required', 'integer', 'min:0'],
            'moderation_note' => ['nullable', 'string', 'max:4000'],
        ];
    }
}
