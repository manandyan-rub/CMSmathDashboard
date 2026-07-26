<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class Achievement extends Model
{
    use HasCmsGovernance;

    protected $fillable = ['key', 'icon', 'badge_color', 'criteria', 'xp_reward', 'active'];

    protected function casts(): array
    {
        return ['criteria' => 'array', 'active' => 'boolean'];
    }
}
