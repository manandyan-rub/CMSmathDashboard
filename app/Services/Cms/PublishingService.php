<?php

namespace App\Services\Cms;

use App\Models\ContentWorkflow;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PublishingService
{
    private const TRANSITIONS = [
        'draft' => ['in_review', 'archived'],
        'in_review' => ['draft', 'approved', 'archived'],
        'approved' => ['in_review', 'published', 'archived'],
        'published' => ['approved', 'archived'],
        'archived' => ['draft'],
    ];

    public function __construct(
        private readonly AuditService $audit,
        private readonly ContentVersionService $versions,
    ) {
    }

    public function transition(
        Model $model,
        string $state,
        ?string $comment = null,
        ?string $scheduledAt = null,
    ): ContentWorkflow {
        if (!in_array($state, ContentWorkflow::STATES, true)) {
            throw ValidationException::withMessages(['state' => 'Unknown publishing state.']);
        }

        return DB::transaction(function () use ($model, $state, $comment, $scheduledAt) {
            $workflow = ContentWorkflow::firstOrNew([
                'workflowable_type' => $model->getMorphClass(),
                'workflowable_id' => $model->getKey(),
            ]);
            $oldState = $workflow->exists ? $workflow->state : 'draft';

            if ($state !== $oldState && !in_array($state, self::TRANSITIONS[$oldState] ?? [], true)) {
                throw ValidationException::withMessages([
                    'state' => "Content cannot move from {$oldState} to {$state}.",
                ]);
            }

            $userId = request()->user()?->id;
            $workflow->state = $state;
            $workflow->reviewer_comment = $comment;

            if ($state === 'in_review') {
                $workflow->submitted_by = $userId;
                $workflow->submitted_at = now();
            }
            if (in_array($state, ['approved', 'draft'], true) && $oldState === 'in_review') {
                $workflow->reviewed_by = $userId;
                $workflow->reviewed_at = now();
            }
            if ($state === 'published') {
                $workflow->published_by = $userId;
                $workflow->published_at = $scheduledAt ? null : now();
                $workflow->scheduled_at = $scheduledAt;
            }
            if ($state !== 'published') {
                $workflow->scheduled_at = null;
            }

            $workflow->save();
            $this->versions->capture(
                $model,
                ['publishing_state' => $oldState],
                ['publishing_state' => $state, 'scheduled_at' => $scheduledAt],
                $comment
            );
            $this->audit->record(
                "content.{$state}",
                $model,
                ['state' => $oldState],
                ['state' => $state, 'scheduled_at' => $scheduledAt],
                $comment
            );

            return $workflow->fresh();
        });
    }
}
