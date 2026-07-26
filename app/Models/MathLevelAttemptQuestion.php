<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MathLevelAttemptQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'attempt_id',
        'source_question_id',
        'question',
        'explanation',
        'order',
        'points',
    ];

    public function attempt()
    {
        return $this->belongsTo(MathLevelAttempt::class, 'attempt_id');
    }

    public function sourceQuestion()
    {
        return $this->belongsTo(MathLevelQuestion::class, 'source_question_id');
    }

    public function options()
    {
        return $this->hasMany(MathLevelAttemptQuestionOption::class, 'question_id')->orderBy('order');
    }
}
