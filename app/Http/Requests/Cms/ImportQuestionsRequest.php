<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class ImportQuestionsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasCmsPermission('questions.manage') ?? false;
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*' => ['required', 'array'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
