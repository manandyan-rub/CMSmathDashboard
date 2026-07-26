<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GameSession extends Model
{
    protected $fillable = [
        'game_definition_id',
        'user_id',
        'score',
        'completed',
        'duration_seconds',
        'mistakes',
        'started_at',
        'finished_at',
    ];

    protected function casts(): array
    {
        return [
            'completed' => 'boolean',
            'mistakes' => 'array',
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }
}
