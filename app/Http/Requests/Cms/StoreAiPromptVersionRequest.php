<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class StoreAiPromptVersionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('ai.manage');
    }

    public function rules(): array
    {
        return [
            'system_prompt' => ['required', 'string', 'max:100000'],
            'user_prompt_template' => ['required', 'string', 'max:100000'],
            'input_schema' => ['nullable', 'array'],
            'output_schema' => ['nullable', 'array'],
            'temperature' => ['nullable', 'numeric', 'min:0', 'max:2'],
            'max_output_tokens' => ['nullable', 'integer', 'min:1', 'max:100000'],
            'change_reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
