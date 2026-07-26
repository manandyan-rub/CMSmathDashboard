<?php

namespace App\Policies;

use App\Models\PlatformSetting;
use App\Models\User;
use App\Policies\Concerns\ChecksCmsPermission;

class PlatformSettingPolicy
{
    use ChecksCmsPermission;

    public function viewAny(User $user): bool { return $this->allowed($user, 'settings.view'); }
    public function update(User $user, PlatformSetting $setting): bool
    {
        return $setting->editable && $this->allowed($user, 'settings.manage');
    }
}
