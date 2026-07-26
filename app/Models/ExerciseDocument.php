<?php

namespace App\Models;

use App\Models\Concerns\HasCmsGovernance;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ExerciseDocument extends Model
{
    use HasCmsGovernance, HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'status',
        'creation_source',
        'ai_generated',
        'content_json',
        'content_html',
        'language',
        'page_count',
        'publication_status',
        'moderation_status',
        'moderated_by',
        'moderated_at',
        'meta',
        'generated_at',
        'published_at',
        'featured_at',
        'archived_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'ai_generated' => 'boolean',
        'generated_at' => 'datetime',
        'published_at' => 'datetime',
        'moderated_at' => 'datetime',
        'featured_at' => 'datetime',
        'archived_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ExerciseItem::class, 'document_id')->orderBy('item_order');
    }

    public function generationLogs(): HasMany
    {
        return $this->hasMany(ExerciseGenerationLog::class, 'document_id')->latest('id');
    }

    public function moderator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'moderated_by');
    }

    public function moderationNotes(): HasMany
    {
        return $this->hasMany(ModerationNote::class, 'document_id')->latest();
    }
}
