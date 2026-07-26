<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentWorkflow extends Model
{
    public const STATES = ['draft', 'in_review', 'approved', 'published', 'archived'];

    protected $fillable = [
        'workflowable_type',
        'workflowable_id',
        'state',
        'submitted_by',
        'submitted_at',
        'reviewed_by',
        'reviewed_at',
        'reviewer_comment',
        'published_by',
        'published_at',
        'scheduled_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'published_at' => 'datetime',
            'scheduled_at' => 'datetime',
        ];
    }

    public function workflowable()
    {
        return $this->morphTo();
    }
}
