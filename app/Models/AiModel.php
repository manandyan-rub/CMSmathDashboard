<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModel extends Model
{
    protected $fillable = [
        'provider_id',
        'key',
        'name',
        'max_output_tokens',
        'default_temperature',
        'timeout_seconds',
        'retry_count',
        'input_cost_per_million',
        'output_cost_per_million',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'default_temperature' => 'decimal:2',
            'input_cost_per_million' => 'decimal:6',
            'output_cost_per_million' => 'decimal:6',
        ];
    }

    public function provider()
    {
        return $this->belongsTo(AiProvider::class);
    }
}
