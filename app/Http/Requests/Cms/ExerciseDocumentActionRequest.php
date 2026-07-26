<?php

namespace App\Http\Requests\Cms;

use Illuminate\Foundation\Http\FormRequest;

class ExerciseDocumentActionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return (bool) $this->user()?->hasCmsPermission('exercise_documents.moderate');
    }

    public function rules(): array
    {
        return [
            'action' => ['required', 'in:submit_review,approve,reject,publish,unpublish,archive,duplicate,delete,transfer,feature,unfeature'],
            'reason' => ['required', 'string', 'min:3', 'max:4000'],
            'target_user_id' => ['required_if:action,transfer', 'nullable', 'integer', 'exists:users,id'],
            'confirmed' => ['required_if:action,delete', 'nullable', 'accepted'],
        ];
    }
}
