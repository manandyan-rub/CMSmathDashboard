<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;

class CalculatorField extends Model
{
    use HasCmsGovernance;

    protected $fillable = [
        'calculator_content_id',
        'key',
        'input_type',
        'example_values',
        'supported_units',
        'display_order',
        'active',
    ];

    protected function casts(): array
    {
        return [
            'example_values' => 'array',
            'supported_units' => 'array',
            'active' => 'boolean',
        ];
    }
}
