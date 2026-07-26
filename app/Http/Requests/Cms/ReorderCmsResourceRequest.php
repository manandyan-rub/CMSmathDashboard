<?php

namespace App\Http\Requests\Cms;

use App\Services\Cms\CmsResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;

class ReorderCmsResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(CmsResourceRegistry::class)->managePermission($this->route('resource'));

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return [
            'items' => ['required', 'array', 'min:1', 'max:500'],
            'items.*.id' => ['required', 'integer'],
            'items.*.order' => ['required', 'integer', 'min:0'],
        ];
    }
}
