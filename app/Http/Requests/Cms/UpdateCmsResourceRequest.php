<?php

namespace App\Http\Requests\Cms;

use App\Services\Cms\CmsResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsResourceRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(CmsResourceRegistry::class)->managePermission($this->route('resource'));

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return array_merge(
            app(CmsResourceRegistry::class)->rules(
                $this->route('resource'),
                (int) $this->route('id')
            ),
            ['reason' => ['nullable', 'string', 'max:2000']]
        );
    }
}
