<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PracticeTemplate extends Model
{
    use HasCmsGovernance, SoftDeletes;

    protected $fillable = [
        'key',
        'topic',
        'calculator_content_id',
        'difficulty',
        'entered_fields',
        'requested_fields',
        'value_ranges',
        'integer_only',
        'exact_value',
        'decimal_precision',
        'allowed_units',
        'xp_reward',
        'active',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'entered_fields' => 'array',
            'requested_fields' => 'array',
            'value_ranges' => 'array',
            'allowed_units' => 'array',
            'integer_only' => 'boolean',
            'exact_value' => 'boolean',
            'active' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    public function calculator()
    {
        return $this->belongsTo(CalculatorContent::class, 'calculator_content_id');
    }
}
