<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExerciseGenerationLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'document_id',
        'source',
        'provider_id',
        'model_id',
        'prompt_version_id',
        'feature',
        'status',
        'prompt',
        'request_json',
        'response_raw',
        'parsed_json',
        'error',
        'input_tokens',
        'output_tokens',
        'estimated_cost',
        'latency_ms',
        'retry_count',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'estimated_cost' => 'decimal:6',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(ExerciseDocument::class, 'document_id');
    }

    public function provider(): BelongsTo
    {
        return $this->belongsTo(AiProvider::class);
    }

    public function model(): BelongsTo
    {
        return $this->belongsTo(AiModel::class);
    }

    public function promptVersion(): BelongsTo
    {
        return $this->belongsTo(AiPromptVersion::class);
    }
}
