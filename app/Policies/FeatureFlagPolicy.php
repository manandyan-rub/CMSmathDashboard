<?php

namespace App\Policies;

use App\Models\FeatureFlag;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class FeatureFlagPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'settings.view'); }
    public function update(User $user, FeatureFlag $flag): bool { return $this->allowed($user, 'features.manage'); }
}
