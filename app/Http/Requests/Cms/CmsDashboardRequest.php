<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CmsDashboardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('dashboard.view');
    }

    public function rules(): array
    {
        return [
            'preset' => ['nullable', Rule::in([
                'today',
                'last_7_days',
                'last_30_days',
                'this_month',
                'last_3_months',
                'this_year',
                'custom',
            ])],
            'date_from' => ['required_if:preset,custom', 'nullable', 'date'],
            'date_to' => ['required_if:preset,custom', 'nullable', 'date', 'after_or_equal:date_from'],
            'locale' => ['nullable', Rule::in(config('cms.locales', ['en']))],
        ];
    }
}
