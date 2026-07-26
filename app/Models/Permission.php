<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Permission extends Model
{
    protected $fillable = ['key', 'label', 'group'];

    public function roles()
    {
        return $this->hasMany(RolePermission::class);
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'user_permissions')
            ->withPivot(['allowed', 'granted_by'])
            ->withTimestamps();
    }
}
