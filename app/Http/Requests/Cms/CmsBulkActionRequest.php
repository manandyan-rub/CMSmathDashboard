<?php

namespace App\Http\Requests\Cms;

use App\Services\Cms\CmsResourceRegistry;
use Illuminate\Foundation\Http\FormRequest;

class CmsBulkActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $permission = app(CmsResourceRegistry::class)->managePermission($this->route('resource'));

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer'],
            'action' => ['required', 'in:activate,deactivate,archive'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ];
    }
}
