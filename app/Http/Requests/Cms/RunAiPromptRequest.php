<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class RunAiPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('ai.manage');
    }

    public function rules(): array
    {
        return [
            'input' => ['required', 'array'],
            'save_as_content' => ['prohibited'],
        ];
    }
}
