<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ExportRequest;
use App\Services\Cms\AuditService;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    private const DEFINITIONS = [
        'users' => ['users.view', ['id', 'name', 'email', 'role', 'status', 'locale', 'country_id', 'email_verified_at', 'last_active_at', 'created_at']],
        'progress' => ['users.view', ['id', 'user_id', 'level_id', 'unlocked', 'completed', 'best_score', 'best_stars', 'total_attempts', 'last_attempted_at']],
        'attempts' => ['users.view', ['id', 'user_id', 'level_id', 'score', 'correct_answers', 'wrong_answers', 'passed', 'xp_earned', 'started_at', 'finished_at']],
        'level-analytics' => ['levels.view', ['level_id', 'attempts', 'completions', 'passes', 'average_score', 'average_hints', 'average_duration']],
        'questions' => ['questions.view', ['id', 'level_id', 'topic', 'difficulty', 'question', 'type', 'answer_config', 'tolerance', 'accepted_unit', 'points', 'xp_value', 'active', 'created_at']],
        'ai-usage' => ['ai.view', ['id', 'user_id', 'document_id', 'source', 'feature', 'status', 'model_id', 'input_tokens', 'output_tokens', 'estimated_cost', 'latency_ms', 'retry_count', 'started_at', 'finished_at']],
        'exercise-documents' => ['exercise_documents.view', ['id', 'user_id', 'title', 'status', 'creation_source', 'ai_generated', 'language', 'page_count', 'publication_status', 'moderation_status', 'published_at', 'updated_at']],
        'xp-ledger' => ['gamification.view', ['id', 'user_id', 'amount', 'balance_after', 'source', 'reason', 'administrator_id', 'occurred_at']],
        'audit-logs' => ['audit.view', ['id', 'administrator_id', 'action', 'entity_type', 'entity_id', 'reason', 'ip_address', 'request_id', 'created_at']],
    ];

    public function __invoke(
        Request $request,
        string $resource,
        AuditService $audit,
    ): StreamedResponse {
        $definition = self::DEFINITIONS[$resource] ?? null;
        if (!$definition) {
            throw ValidationException::withMessages(['resource' => 'Unsupported export resource.']);
        }
        abort_unless($request->user()->hasCmsPermission('exports.create'), 403);
        abort_unless($request->user()->hasCmsPermission($definition[0]), 403);
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        [$query, $columns, $dateColumn] = $this->query($resource, $definition[1]);
        if (!empty($validated['date_from'])) {
            $query->where($dateColumn, '>=', $validated['date_from'].' 00:00:00');
        }
        if (!empty($validated['date_to'])) {
            $query->where($dateColumn, '<=', $validated['date_to'].' 23:59:59');
        }

        $export = ExportRequest::create([
            'requested_by' => $request->user()->id,
            'resource' => $resource,
            'format' => 'csv',
            'filters' => $validated,
            'status' => 'processing',
        ]);
        $audit->record('export.requested', $export, [], ['resource' => $resource]);

        return response()->streamDownload(function () use ($query, $columns, $export) {
            $output = fopen('php://output', 'wb');
            fputcsv($output, $columns);
            try {
                $query->orderBy($columns[0])->chunk(1000, function ($rows) use ($output, $columns) {
                    foreach ($rows as $row) {
                        fputcsv($output, collect($columns)->map(function (string $column) use ($row) {
                            $value = $row->{$column};

                            $value = is_array($value) || is_object($value)
                                ? json_encode($value, JSON_UNESCAPED_UNICODE)
                                : $value;

                            return is_string($value) && preg_match('/^[=+\-@]/', $value)
                                ? "'{$value}"
                                : $value;
                        })->all());
                    }
                });
                $export->update(['status' => 'completed', 'completed_at' => now()]);
            } catch (\Throwable $exception) {
                $export->update(['status' => 'failed', 'error' => $exception->getMessage()]);
                throw $exception;
            } finally {
                fclose($output);
            }
        }, "{$resource}-".now()->format('Y-m-d-His').'.csv', ['Content-Type' => 'text/csv']);
    }

    private function query(string $resource, array $columns): array
    {
        return match ($resource) {
            'users' => [DB::table('users')->select($columns), $columns, 'created_at'],
            'progress' => [DB::table('math_user_level_progress')->select($columns), $columns, 'updated_at'],
            'attempts' => [DB::table('math_level_attempts')->select($columns), $columns, 'finished_at'],
            'level-analytics' => [
                DB::table('math_level_attempts')
                    ->selectRaw(
                        'level_id, COUNT(*) as attempts, '.
                        'SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) as completions, '.
                        'SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passes, '.
                        'AVG(score) as average_score, AVG(hints_used) as average_hints, '.
                        'AVG(duration_seconds) as average_duration'
                    )
                    ->groupBy('level_id'),
                $columns,
                'finished_at',
            ],
            'questions' => [DB::table('math_level_questions')->select($columns), $columns, 'created_at'],
            'ai-usage' => [DB::table('exercise_generation_logs')->select($columns), $columns, 'started_at'],
            'exercise-documents' => [DB::table('exercise_documents')->select($columns), $columns, 'updated_at'],
            'xp-ledger' => [DB::table('xp_ledger_entries')->select($columns), $columns, 'occurred_at'],
            'audit-logs' => [DB::table('audit_logs')->select($columns), $columns, 'created_at'],
        };
    }
}
