<?php

namespace App\Http\Requests\Cms;

use App\Services\Cms\CmsResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;

class CmsResourceActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(CmsResourceRegistry::class)->managePermission($this->route('resource'));

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return [
            'mode' => ['nullable', 'in:archive,delete'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
