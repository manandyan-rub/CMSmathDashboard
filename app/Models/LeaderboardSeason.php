<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LeaderboardSeason extends Model
{
    protected $fillable = ['key', 'title', 'starts_at', 'ends_at', 'active', 'excluded_user_ids', 'rules'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'active' => 'boolean',
            'excluded_user_ids' => 'array',
            'rules' => 'array',
        ];
    }
}
