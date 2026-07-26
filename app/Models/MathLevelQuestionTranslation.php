<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MathLevelQuestionTranslation extends Model
{
    protected $fillable = [
        'question_id',
        'locale',
        'question',
        'explanation',
        'hints',
        'reviewed',
        'source_updated_at',
    ];

    protected function casts(): array
    {
        return ['hints' => 'array', 'reviewed' => 'boolean', 'source_updated_at' => 'datetime'];
    }
}
