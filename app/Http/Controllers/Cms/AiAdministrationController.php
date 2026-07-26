<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\RunAiPromptRequest;
use App\Http\Requests\Cms\StoreAiPromptVersionRequest;
use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\ExerciseGenerationLog;
use App\Services\Cms\AiPromptRunnerService;
use App\Services\Cms\AuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AiAdministrationController extends Controller
{
    public function storeVersion(
        StoreAiPromptVersionRequest $request,
        AiPrompt $prompt,
        AuditService $audit,
    ): JsonResponse {
        $version = DB::transaction(function () use ($request, $prompt, $audit) {
            $number = ((int) $prompt->versions()->lockForUpdate()->max('version')) + 1;
            $version = $prompt->versions()->create(array_merge($request->validated(), [
                'version' => $number,
                'author_id' => $request->user()->id,
                'active' => false,
            ]));
            $audit->record(
                'ai.prompt_version_created',
                $prompt,
                [],
                ['version' => $number],
                $request->validated('change_reason')
            );

            return $version;
        });

        return response()->json(['message' => 'Prompt version created.', 'version' => $version], 201);
    }

    public function activateVersion(
        Request $request,
        AiPrompt $prompt,
        AiPromptVersion $version,
        AuditService $audit,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('ai.prompts.publish'), 403);
        abort_unless($version->prompt_id === $prompt->id, 404);
        $validated = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:2000']]);

        DB::transaction(function () use ($prompt, $version, $validated, $audit) {
            $previous = $prompt->versions()->where('active', true)->first();
            $prompt->versions()->update(['active' => false]);
            $version->update(['active' => true]);
            $prompt->update(['active' => true]);
            $audit->record(
                'ai.prompt_version_activated',
                $prompt,
                ['version' => $previous?->version],
                ['version' => $version->version],
                $validated['reason']
            );
        });

        return response()->json(['message' => 'Prompt version activated.']);
    }

    public function compare(
        Request $request,
        AiPrompt $prompt,
        AiPromptVersion $left,
        AiPromptVersion $right,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('ai.view'), 403);
        abort_unless($left->prompt_id === $prompt->id && $right->prompt_id === $prompt->id, 404);

        return response()->json([
            'left' => $left,
            'right' => $right,
            'changed_fields' => collect([
                'system_prompt',
                'user_prompt_template',
                'input_schema',
                'output_schema',
                'temperature',
                'max_output_tokens',
            ])->filter(fn ($field) => $left->{$field} !== $right->{$field})->values(),
        ]);
    }

    public function test(
        RunAiPromptRequest $request,
        AiPrompt $prompt,
        AiPromptRunnerService $runner,
    ): JsonResponse {
        $log = $runner->run($prompt, $request->validated('input'), 'cms_test');

        return response()->json([
            'message' => $log->status === 'completed' ? 'AI test completed.' : 'AI test failed.',
            'result' => [
                'log_id' => $log->id,
                'status' => $log->status,
                'raw_output' => $log->response_raw,
                'parsed_output' => $log->parsed_json ? json_decode($log->parsed_json, true) : null,
                'error' => $log->error,
                'input_tokens' => $log->input_tokens,
                'output_tokens' => $log->output_tokens,
                'estimated_cost' => $log->estimated_cost,
                'latency_ms' => $log->latency_ms,
            ],
        ], $log->status === 'completed' ? 200 : 422);
    }

    public function retry(
        Request $request,
        ExerciseGenerationLog $generationLog,
        AiPromptRunnerService $runner,
        AuditService $audit,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('ai.manage'), 403);
        abort_unless($generationLog->status === 'failed', 422, 'Only failed generations can be retried.');
        $version = $generationLog->promptVersion;
        if (!$version) {
            throw ValidationException::withMessages([
                'generation' => 'This generation is not tied to a CMS-managed prompt and cannot be retried here.',
            ]);
        }
        $requestData = json_decode($generationLog->request_json ?: '{}', true);
        $input = $requestData['input'] ?? null;
        if (!is_array($input)) {
            throw ValidationException::withMessages(['generation' => 'The original test input is unavailable.']);
        }

        $newLog = $runner->run($version->prompt, $input, 'cms_retry');
        $newLog->update(['retry_count' => $generationLog->retry_count + 1]);
        $audit->record(
            'ai.generation_retried',
            $generationLog,
            ['status' => $generationLog->status],
            ['new_log_id' => $newLog->id],
            'Manual CMS retry'
        );

        return response()->json(['message' => 'Generation retried.', 'log_id' => $newLog->id]);
    }

    public function analytics(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('ai.view'), 403);
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'feature' => ['nullable', 'string', 'max:80'],
            'model_id' => ['nullable', 'integer', 'exists:ai_models,id'],
        ]);
        $query = ExerciseGenerationLog::query();
        if (!empty($validated['date_from'])) {
            $query->where('started_at', '>=', $validated['date_from'].' 00:00:00');
        }
        if (!empty($validated['date_to'])) {
            $query->where('started_at', '<=', $validated['date_to'].' 23:59:59');
        }
        if (!empty($validated['feature'])) {
            $query->where('feature', $validated['feature']);
        }
        if (!empty($validated['model_id'])) {
            $query->where('model_id', $validated['model_id']);
        }

        $summary = (clone $query)->selectRaw(
            'COUNT(*) as requests, '.
            "SUM(CASE WHEN status = 'failed' OR error IS NOT NULL THEN 1 ELSE 0 END) as failures, ".
            'SUM(COALESCE(retry_count, 0)) as retries, '.
            'SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) as tokens, '.
            'SUM(COALESCE(estimated_cost, 0)) as estimated_cost, '.
            'AVG(latency_ms) as average_latency_ms'
        )->first();

        return response()->json([
            'summary' => $summary,
            'by_feature' => (clone $query)
                ->selectRaw('COALESCE(feature, source) as label, COUNT(*) as value')
                ->groupBy('label')->orderByDesc('value')->get(),
            'by_model' => (clone $query)
                ->leftJoin('ai_models', 'ai_models.id', '=', 'exercise_generation_logs.model_id')
                ->selectRaw("COALESCE(ai_models.name, 'Unassigned') as label, COUNT(*) as value")
                ->groupBy('ai_models.id', 'ai_models.name')->orderByDesc('value')->get(),
            'by_user' => (clone $query)
                ->join('users', 'users.id', '=', 'exercise_generation_logs.user_id')
                ->selectRaw('users.id, users.name as label, COUNT(*) as value')
                ->groupBy('users.id', 'users.name')->orderByDesc('value')->limit(25)->get(),
        ]);
    }
}
