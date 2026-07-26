<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class AdjustUserXpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('users.xp.adjust');
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'integer', 'between:-1000000,1000000', 'not_in:0'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
