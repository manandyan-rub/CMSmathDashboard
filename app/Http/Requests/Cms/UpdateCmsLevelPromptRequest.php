<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCmsLevelPromptRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('levels.manage');
    }

    public function rules(): array
    {
        return [
            'prompt' => ['required', 'string'],
            'question_count' => ['required', 'integer', 'min:1', 'max:50'],
            'options_count' => ['required', 'integer', 'min:2', 'max:8'],
            'locale' => ['required', Rule::in(['en', 'hy', 'ru', 'fr'])],
        ];
    }
}
