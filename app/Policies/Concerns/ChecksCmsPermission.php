<?php

namespace App\Policies\Concerns;

use App\Models\User;

trait ChecksCmsPermission
{
    protected function allowed(User $user, string $permission): bool
    {
        return $user->hasCmsPermission($permission);
    }
}
