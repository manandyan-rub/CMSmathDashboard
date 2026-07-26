<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class GameDefinition extends Model
{
    use HasCmsGovernance;

    protected $fillable = [
        'key',
        'icon',
        'active',
        'difficulty',
        'number_range',
        'question_count',
        'time_limit',
        'xp_reward',
        'daily_limit',
        'leaderboard_included',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'number_range' => 'array',
            'leaderboard_included' => 'boolean',
        ];
    }

    public function sessions()
    {
        return $this->hasMany(GameSession::class);
    }
}
