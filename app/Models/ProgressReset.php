<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProgressReset extends Model
{
    protected $fillable = [
        'user_id',
        'level_id',
        'administrator_id',
        'reason',
        'snapshot',
        'reset_at',
    ];

    protected function casts(): array
    {
        return ['snapshot' => 'array', 'reset_at' => 'datetime'];
    }
}
