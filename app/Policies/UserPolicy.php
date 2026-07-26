<?php

namespace App\Policies;

use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class UserPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'users.view'); }
    public function view(User $user, User $subject): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'users.manage'); }
    public function update(User $user, User $subject): bool { return $this->allowed($user, 'users.manage'); }
    public function delete(User $user, User $subject): bool
    {
        return $user->isNot($subject) && $this->allowed($user, 'users.manage');
    }
    public function manageRoles(User $user): bool { return $this->allowed($user, 'users.roles.manage'); }
    public function resetProgress(User $user): bool { return $this->allowed($user, 'users.progress.reset'); }
    public function adjustXp(User $user): bool { return $this->allowed($user, 'users.xp.adjust'); }
}
