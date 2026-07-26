<?php

namespace App\Policies;

use App\Models\MathLevel;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class MathLevelPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'levels.view'); }
    public function view(User $user, MathLevel $level): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'levels.manage'); }
    public function update(User $user, MathLevel $level): bool { return $this->allowed($user, 'levels.manage'); }
    public function delete(User $user, MathLevel $level): bool { return $this->allowed($user, 'levels.manage'); }
}
