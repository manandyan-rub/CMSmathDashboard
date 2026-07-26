<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class MathLevelQuestion extends Model
{
    use HasCmsGovernance, HasFactory, SoftDeletes;

    protected $fillable = [
        'level_id',
        'topic',
        'difficulty',
        'question',
        'type',
        'explanation',
        'correct_answer',
        'answer_config',
        'tolerance',
        'accepted_unit',
        'hints',
        'image_path',
        'formula_id',
        'calculator_key',
        'order',
        'points',
        'xp_value',
        'active',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'answer_config' => 'array',
            'hints' => 'array',
            'active' => 'boolean',
            'tolerance' => 'decimal:6',
            'archived_at' => 'datetime',
        ];
    }

    public function level()
    {
        return $this->belongsTo(MathLevel::class, 'level_id');
    }

    public function options()
    {
        return $this->hasMany(MathLevelQuestionOption::class, 'question_id')->orderBy('order');
    }

    public function translations()
    {
        return $this->hasMany(MathLevelQuestionTranslation::class, 'question_id');
    }

    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }
}
