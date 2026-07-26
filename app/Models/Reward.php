<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class Reward extends Model
{
    use HasCmsGovernance;

    protected $fillable = ['key', 'type', 'cost_xp', 'payload', 'stock', 'active', 'starts_at', 'ends_at'];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'active' => 'boolean',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
        ];
    }
}
