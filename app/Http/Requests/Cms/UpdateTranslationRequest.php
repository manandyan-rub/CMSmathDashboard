<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTranslationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('translations.manage');
    }

    public function rules(): array
    {
        return [
            'locale' => ['required', 'in:'.implode(',', config('cms.locales', ['en']))],
            'values' => ['required', 'array'],
            'reviewed' => ['nullable', 'boolean'],
            'machine_draft' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
