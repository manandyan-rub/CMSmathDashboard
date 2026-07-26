<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'document_id',
        'item_order',
        'type',
        'question',
        'options',
        'answer',
        'explanation',
        'difficulty',
        'points',
        'source',
        'edited_after_ai',
        'source_metadata',
    ];

    protected $casts = [
        'options' => 'array',
        'edited_after_ai' => 'boolean',
        'source_metadata' => 'array',
    ];

    public function document(): BelongsTo
    {
        return $this->belongsTo(ExerciseDocument::class, 'document_id');
    }
}
