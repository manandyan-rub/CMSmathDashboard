<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsLevelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    public function rules(): array
    {
        return [
            'difficulty' => ['required', 'string', 'max:50'],
            'passing_score' => ['required', 'integer', 'min:1', 'max:100'],
            'xp_reward' => ['required', 'integer', 'min:0', 'max:10000'],
            'time_limit' => ['nullable', 'integer', 'min:5', 'max:7200'],
            'active' => ['required', 'boolean'],
        ];
    }
}

