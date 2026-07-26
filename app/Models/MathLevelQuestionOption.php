<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MathLevelQuestionOption extends Model
{
    use HasFactory;

    protected $fillable = [
        'question_id',
        'option_text',
        'explanation',
        'is_correct',
        'order',
    ];

    protected function casts(): array
    {
        return ['is_correct' => 'boolean'];
    }

    public function question()
    {
        return $this->belongsTo(MathLevelQuestion::class, 'question_id');
    }

    public function translations()
    {
        return $this->hasMany(MathLevelQuestionOptionTranslation::class, 'option_id');
    }
}
