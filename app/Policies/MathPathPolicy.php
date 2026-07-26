<?php

namespace App\Policies;

use App\Models\MathPath;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class MathPathPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'paths.view'); }
    public function view(User $user, MathPath $path): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'paths.manage'); }
    public function update(User $user, MathPath $path): bool { return $this->allowed($user, 'paths.manage'); }
    public function delete(User $user, MathPath $path): bool { return $this->allowed($user, 'paths.manage'); }
}
