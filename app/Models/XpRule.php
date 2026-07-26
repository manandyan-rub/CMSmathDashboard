<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class XpRule extends Model
{
    use HasCmsGovernance;

    protected $fillable = [
        'action_type',
        'topic',
        'difficulty',
        'base_xp',
        'bonus_xp',
        'daily_cap',
        'maximum_rewarded_attempts',
        'repeated_answer_protection',
        'starts_at',
        'ends_at',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'repeated_answer_protection' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
        ];
    }
}
