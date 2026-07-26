<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Formula extends Model
{
    use HasCmsGovernance, SoftDeletes;

    protected $fillable = [
        'group_id',
        'category',
        'formula',
        'example',
        'variables',
        'units',
        'usage_notes',
        'visual_proof_reference',
        'sort_order',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'variables' => 'array',
            'units' => 'array',
            'archived_at' => 'datetime',
        ];
    }

    public function group()
    {
        return $this->belongsTo(FormulaGroup::class);
    }

    public function translations(): HasMany
    {
        return $this->hasMany(FormulaTranslation::class);
    }

    public function step(): HasOne
    {
        return $this->hasOne(FormulaStep::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(FormulaStep::class);
    }

    public function relations(): HasMany
    {
        return $this->hasMany(FormulaRelation::class);
    }
}
