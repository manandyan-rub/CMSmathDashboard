<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeatureFlag extends Model
{
    protected $fillable = [
        'key',
        'title',
        'description',
        'enabled',
        'environment',
        'starts_at',
        'ends_at',
        'public',
    ];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'public' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
