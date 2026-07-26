<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalculatorContent extends Model
{
    use HasCmsGovernance, SoftDeletes;

    protected $fillable = [
        'key',
        'category',
        'icon',
        'active',
        'display_order',
        'supported_units',
        'default_unit',
        'default_precision',
        'metadata',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'supported_units' => 'array',
            'metadata' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function fields()
    {
        return $this->hasMany(CalculatorField::class)->orderBy('display_order');
    }

    public function relations()
    {
        return $this->hasMany(CalculatorRelation::class);
    }
}
