<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MathLevelQuestionOptionTranslation extends Model
{
    protected $fillable = ['option_id', 'locale', 'label', 'explanation', 'reviewed'];

    protected function casts(): array
    {
        return ['reviewed' => 'boolean'];
    }
}
