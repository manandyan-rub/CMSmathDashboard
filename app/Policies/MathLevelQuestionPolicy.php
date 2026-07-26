<?php

namespace App\Policies;

use App\Models\MathLevelQuestion;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class MathLevelQuestionPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'questions.view'); }
    public function view(User $user, MathLevelQuestion $question): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'questions.manage'); }
    public function update(User $user, MathLevelQuestion $question): bool { return $this->allowed($user, 'questions.manage'); }
    public function delete(User $user, MathLevelQuestion $question): bool { return $this->allowed($user, 'questions.manage'); }
}
