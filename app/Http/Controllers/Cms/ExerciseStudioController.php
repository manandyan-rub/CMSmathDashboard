<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ExerciseDocumentActionRequest;
use App\Models\ExerciseDocument;
use App\Services\Cms\ExerciseDocumentAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExerciseStudioController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', ExerciseDocument::class);
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'owner_id' => ['nullable', 'integer', 'exists:users,id'],
            'status' => ['nullable', 'string', 'max:30'],
            'source' => ['nullable', 'string', 'max:30'],
            'locale' => ['nullable', 'string', 'max:10'],
            'publication_status' => ['nullable', 'in:draft,in_review,approved,published,archived'],
            'moderation_status' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $query = ExerciseDocument::query()
            ->with('user:id,name,email,role')
            ->withCount(['items', 'generationLogs', 'moderationNotes']);
        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $query->where('title', 'like', "%{$search}%");
        }
        foreach ([
            'owner_id' => 'user_id',
            'status' => 'status',
            'source' => 'creation_source',
            'locale' => 'language',
            'publication_status' => 'publication_status',
            'moderation_status' => 'moderation_status',
        ] as $filter => $column) {
            if (!empty($validated[$filter])) {
                $query->where($column, $validated[$filter]);
            }
        }
        $documents = $query->latest('updated_at')->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'items' => $documents->items(),
            'pagination' => [
                'page' => $documents->currentPage(),
                'last_page' => $documents->lastPage(),
                'total' => $documents->total(),
            ],
        ]);
    }

    public function show(Request $request, ExerciseDocument $document): JsonResponse
    {
        Gate::authorize('view', $document);
        $document->load([
            'user:id,name,email,role',
            'items',
            'generationLogs' => fn ($query) => $query->select([
                'id',
                'user_id',
                'document_id',
                'source',
                'feature',
                'status',
                'error',
                'started_at',
                'finished_at',
                'created_at',
            ])->limit(30),
            'moderationNotes.administrator:id,name,email',
            'versions.author:id,name,email',
        ]);

        return response()->json(['document' => $document]);
    }

    public function action(
        ExerciseDocumentActionRequest $request,
        ExerciseDocument $document,
        ExerciseDocumentAdministrationService $service,
    ): JsonResponse {
        Gate::authorize('moderate', $document);
        $action = $request->validated('action');
        $updated = $action === 'duplicate'
            ? $service->duplicate($document->load('items'), $request->validated('reason'))
            : $service->apply(
                $document,
                $action,
                $request->validated('reason'),
                $request->validated('target_user_id')
            );

        return response()->json([
            'message' => $action === 'duplicate' ? 'Document duplicated.' : 'Document updated.',
            'document' => $updated,
        ], $action === 'duplicate' ? 201 : 200);
    }

    public function export(Request $request, ExerciseDocument $document): StreamedResponse
    {
        Gate::authorize('view', $document);
        abort_unless($request->user()->hasCmsPermission('exports.create'), 403);
        $format = $request->validate(['format' => ['nullable', 'in:json,html']])['format'] ?? 'json';

        return response()->streamDownload(function () use ($document, $format) {
            if ($format === 'html') {
                echo $document->content_html ?: '';
                return;
            }
            echo json_encode(
                $document->load(['items', 'user:id,name,email'])->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE
            );
        }, "exercise-document-{$document->id}.{$format}", [
            'Content-Type' => $format === 'html' ? 'text/html' : 'application/json',
        ]);
    }
}
