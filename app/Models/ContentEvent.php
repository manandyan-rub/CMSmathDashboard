<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentEvent extends Model
{
    protected $fillable = [
        'user_id',
        'event',
        'entity_type',
        'entity_id',
        'locale',
        'metadata',
        'occurred_at',
    ];

    protected function casts(): array
    {
        return ['metadata' => 'array', 'occurred_at' => 'datetime'];
    }
}
