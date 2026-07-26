<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class PublishContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $state = (string) $this->input('state');
        $permission = in_array($state, ['approved', 'published'], true)
            ? 'content.publish'
            : 'content.review';

        return (bool) $this->user()?->hasCmsPermission($permission);
    }

    public function rules(): array
    {
        return [
            'state' => ['required', 'in:draft,in_review,approved,published,archived'],
            'comment' => ['nullable', 'string', 'max:4000'],
            'scheduled_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
