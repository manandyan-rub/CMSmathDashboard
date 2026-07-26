<?php

namespace App\Policies;

use App\Models\Formula;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class FormulaPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'formulas.view'); }
    public function view(User $user, Formula $formula): bool { return $this->viewAny($user); }
    public function create(User $user): bool { return $this->allowed($user, 'formulas.manage'); }
    public function update(User $user, Formula $formula): bool { return $this->allowed($user, 'formulas.manage'); }
    public function delete(User $user, Formula $formula): bool { return $this->allowed($user, 'formulas.manage'); }
}
