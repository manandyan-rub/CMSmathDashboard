<?php

namespace App\Http\Requests\Cms;

use App\Services\Cms\CmsResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;

class CmsResourceIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(CmsResourceRegistry::class)->viewPermission($this->route('resource'));

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'filter' => ['nullable', 'array'],
            'sort' => ['nullable', 'string', 'max:80'],
            'direction' => ['nullable', 'in:asc,desc'],
            'workflow_state' => ['nullable', 'in:draft,in_review,approved,published,archived'],
            'locale' => ['nullable', 'in:'.implode(',', config('cms.locales', ['en']))],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:'.config('cms.pagination.maximum', 100)],
        ];
    }
}
