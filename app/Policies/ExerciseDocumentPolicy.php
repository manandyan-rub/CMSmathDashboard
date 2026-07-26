<?php

namespace App\Policies;

use App\Models\ExerciseDocument;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class ExerciseDocumentPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'exercise_documents.view'); }
    public function view(User $user, ExerciseDocument $document): bool { return $this->viewAny($user); }
    public function moderate(User $user, ExerciseDocument $document): bool
    {
        return $this->allowed($user, 'exercise_documents.moderate');
    }
}
