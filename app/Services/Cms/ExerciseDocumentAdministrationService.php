<?php

namespace App\Services\Cms;

use App\Models\ExerciseDocument;
use App\Models\ModerationNote;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ExerciseDocumentAdministrationService
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly ContentVersionService $versions,
    ) {
    }

    public function apply(
        ExerciseDocument $document,
        string $action,
        string $reason,
        ?int $targetUserId = null,
    ): ExerciseDocument {
        return DB::transaction(function () use ($document, $action, $reason, $targetUserId) {
            $before = $this->snapshot($document);

            match ($action) {
                'submit_review' => $document->update([
                    'publication_status' => 'in_review',
                    'moderation_status' => 'pending',
                ]),
                'approve' => $document->update([
                    'publication_status' => 'approved',
                    'moderation_status' => 'approved',
                    'moderated_by' => request()->user()->id,
                    'moderated_at' => now(),
                ]),
                'reject' => $document->update([
                    'publication_status' => 'draft',
                    'moderation_status' => 'rejected',
                    'moderated_by' => request()->user()->id,
                    'moderated_at' => now(),
                ]),
                'publish' => $this->publish($document),
                'unpublish' => $document->update([
                    'publication_status' => 'approved',
                    'published_at' => null,
                ]),
                'archive' => $document->update([
                    'publication_status' => 'archived',
                    'archived_at' => now(),
                ]),
                'transfer' => $document->update(['user_id' => $targetUserId]),
                'feature' => $document->update(['featured_at' => now()]),
                'unfeature' => $document->update(['featured_at' => null]),
                'delete' => $document->delete(),
                default => throw ValidationException::withMessages(['action' => 'Unsupported document action.']),
            };

            ModerationNote::create([
                'document_id' => $document->id,
                'administrator_id' => request()->user()->id,
                'status' => $action,
                'note' => $reason,
            ]);
            $after = $document->exists && !$document->trashed()
                ? $this->snapshot($document->fresh())
                : ['deleted' => true];
            $this->versions->capture($document, $before, $after, $reason);
            $this->audit->record("exercise_document.{$action}", $document, $before, $after, $reason);

            return $document->fresh(['user', 'items', 'moderationNotes.administrator']);
        });
    }

    public function duplicate(ExerciseDocument $document, string $reason): ExerciseDocument
    {
        return DB::transaction(function () use ($document, $reason) {
            $copy = $document->replicate([
                'status',
                'publication_status',
                'moderation_status',
                'moderated_by',
                'moderated_at',
                'published_at',
                'featured_at',
                'archived_at',
            ]);
            $copy->title = $document->title.' (Copy)';
            $copy->status = 'draft';
            $copy->publication_status = 'draft';
            $copy->moderation_status = 'unreviewed';
            $copy->save();

            foreach ($document->items as $item) {
                $copy->items()->create($item->replicate()->toArray());
            }
            $this->audit->record(
                'exercise_document.duplicated',
                $copy,
                [],
                ['source_document_id' => $document->id],
                $reason
            );

            return $copy->fresh(['user', 'items']);
        });
    }

    private function publish(ExerciseDocument $document): void
    {
        if (!in_array($document->publication_status, ['approved', 'published'], true)) {
            throw ValidationException::withMessages([
                'action' => 'The document must be approved before publishing.',
            ]);
        }
        $document->update([
            'status' => 'published',
            'publication_status' => 'published',
            'published_at' => now(),
        ]);
    }

    private function snapshot(ExerciseDocument $document): array
    {
        return array_merge($this->audit->modelSnapshot($document), [
            'item_count' => $document->items()->count(),
        ]);
    }
}
