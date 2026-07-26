<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class AuditLogPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'audit.view'); }
    public function view(User $user, AuditLog $log): bool { return $this->viewAny($user); }
}
