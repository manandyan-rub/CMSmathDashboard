<?php

namespace App\Services\Cms;

use App\Models\ContentEvent;
use App\Models\ContentWorkflow;
use App\Models\ExerciseDocument;
use App\Models\ExerciseGenerationLog;
use App\Models\Formula;
use App\Models\MathLevelAttempt;
use App\Models\MathUserProfile;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function get(string $preset, ?string $dateFrom, ?string $dateTo, string $locale): array
    {
        [$start, $end] = $this->range($preset, $dateFrom, $dateTo);
        $cacheKey = sprintf(
            'cms.dashboard.%s.%s.%s',
            $start->format('YmdHi'),
            $end->format('YmdHi'),
            $locale
        );

        return Cache::remember(
            $cacheKey,
            (int) config('cms.dashboard_cache_seconds', 300),
            fn () => $this->calculate($start, $end, $locale, $preset)
        );
    }

    private function calculate(
        CarbonImmutable $start,
        CarbonImmutable $end,
        string $locale,
        string $preset,
    ): array {
        $attempts = MathLevelAttempt::query()->whereBetween('finished_at', [$start, $end]);
        $generationLogs = ExerciseGenerationLog::query()->whereBetween('started_at', [$start, $end]);
        $answers = DB::table('math_level_attempt_answers')->whereBetween('created_at', [$start, $end]);

        $totalAttempts = (clone $attempts)->whereNotNull('finished_at')->count();
        $correctAnswers = (clone $answers)->where('is_correct', true)->count();
        $incorrectAnswers = (clone $answers)->where('is_correct', false)->count();
        $generationCount = (clone $generationLogs)->count();
        $generationFailures = (clone $generationLogs)
            ->where(fn ($query) => $query->where('status', 'failed')->orWhereNotNull('error'))
            ->count();

        $summary = [
            'total_users' => User::count(),
            'total_students' => User::where('role', User::ROLE_STUDENT)->count(),
            'total_lecturers' => User::where('role', User::ROLE_LECTURER)->count(),
            'total_administrators' => User::where('role', User::ROLE_ADMIN)->count(),
            'active_today' => $this->activeSince(now()->startOfDay()),
            'active_week' => $this->activeSince(now()->startOfWeek()),
            'active_month' => $this->activeSince(now()->startOfMonth()),
            'new_registrations' => User::whereBetween('created_at', [$start, $end])->count(),
            'completed_exercises' => $correctAnswers + $incorrectAnswers,
            'completed_level_attempts' => $totalAttempts,
            'average_correctness' => $totalAttempts
                ? round((float) (clone $attempts)->avg('score'), 1)
                : 0,
            'total_xp_awarded' => (int) MathUserProfile::sum('total_xp'),
            'ai_generations' => $generationCount,
            'ai_generation_failures' => $generationFailures,
            'total_exercise_documents' => ExerciseDocument::count(),
            'published_formulas' => Formula::whereHas(
                'workflow',
                fn ($query) => $query->where('state', 'published')
            )->count(),
            'draft_content' => ContentWorkflow::whereIn('state', ['draft', 'in_review'])->count()
                + ExerciseDocument::whereIn('publication_status', ['draft', 'in_review'])->count(),
        ];

        return [
            'range' => [
                'preset' => $preset,
                'from' => $start->toDateString(),
                'to' => $end->toDateString(),
            ],
            'summary' => $summary,
            'charts' => [
                'daily_active_users' => $this->dailyUsers($start, $end),
                'registrations' => $this->dailyCounts('users', 'created_at', $start, $end),
                'exercises_completed' => $this->dailyCounts(
                    'math_level_attempt_answers',
                    'created_at',
                    $start,
                    $end
                ),
                'answer_correctness' => [
                    ['label' => 'Correct', 'value' => $correctAnswers],
                    ['label' => 'Incorrect', 'value' => $incorrectAnswers],
                ],
                'ai_requests' => $this->dailyAi($start, $end),
                'ai_success_failure' => [
                    [
                        'label' => 'Successful',
                        'value' => max(0, $generationCount - $generationFailures),
                    ],
                    ['label' => 'Failed', 'value' => $generationFailures],
                ],
                'users_by_role' => User::query()
                    ->selectRaw('role as label, COUNT(*) as value')
                    ->groupBy('role')
                    ->orderByDesc('value')
                    ->get(),
                'users_by_country' => DB::table('users')
                    ->leftJoin('countries', 'countries.id', '=', 'users.country_id')
                    ->selectRaw("COALESCE(countries.name, 'Unassigned') as label, COUNT(*) as value")
                    ->groupBy('countries.id', 'countries.name')
                    ->orderByDesc('value')
                    ->limit(10)
                    ->get(),
                'users_by_locale' => User::query()
                    ->selectRaw('locale as label, COUNT(*) as value')
                    ->groupBy('locale')
                    ->orderByDesc('value')
                    ->get(),
                'popular_topics' => $this->topicPerformance($locale, false),
                'weakest_topics' => $this->topicPerformance($locale, true),
                'calculators' => DB::table('math_workspace_calculations')
                    ->selectRaw('topic as label, COUNT(*) as value')
                    ->whereBetween('created_at', [$start, $end])
                    ->groupBy('topic')
                    ->orderByDesc('value')
                    ->limit(10)
                    ->get(),
                'formulas' => ContentEvent::query()
                    ->where('entity_type', (new Formula())->getMorphClass())
                    ->where('event', 'viewed')
                    ->whereBetween('occurred_at', [$start, $end])
                    ->selectRaw('entity_id, COUNT(*) as value')
                    ->groupBy('entity_id')
                    ->orderByDesc('value')
                    ->limit(10)
                    ->get()
                    ->map(fn ($row) => [
                        'label' => "Formula #{$row->entity_id}",
                        'value' => (int) $row->value,
                    ]),
            ],
            'ai_usage' => [
                'generations' => $generationCount,
                'successful' => max(0, $generationCount - $generationFailures),
                'failed' => $generationFailures,
                'success_rate' => $generationCount
                    ? round((($generationCount - $generationFailures) / $generationCount) * 100, 1)
                    : 0,
                'average_latency_ms' => (int) ((clone $generationLogs)->avg('latency_ms') ?? 0),
                'tokens' => (int) ((clone $generationLogs)
                    ->selectRaw(
                        'SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) as aggregate_tokens'
                    )
                    ->value('aggregate_tokens') ?? 0),
                'estimated_cost' => round((float) (clone $generationLogs)->sum('estimated_cost'), 4),
            ],
        ];
    }

    private function range(string $preset, ?string $dateFrom, ?string $dateTo): array
    {
        $now = CarbonImmutable::now();

        return match ($preset) {
            'today' => [$now->startOfDay(), $now->endOfDay()],
            'last_7_days' => [$now->subDays(6)->startOfDay(), $now->endOfDay()],
            'this_month' => [$now->startOfMonth(), $now->endOfDay()],
            'last_3_months' => [$now->subMonths(3)->startOfDay(), $now->endOfDay()],
            'this_year' => [$now->startOfYear(), $now->endOfDay()],
            'custom' => [
                CarbonImmutable::parse($dateFrom)->startOfDay(),
                CarbonImmutable::parse($dateTo)->endOfDay(),
            ],
            default => [$now->subDays(29)->startOfDay(), $now->endOfDay()],
        };
    }

    private function activeSince($date): int
    {
        return User::query()
            ->where('last_active_at', '>=', $date)
            ->count();
    }

    private function dailyCounts(
        string $table,
        string $column,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $rows = DB::table($table)
            ->selectRaw("DATE({$column}) as date, COUNT(*) as value")
            ->whereBetween($column, [$start, $end])
            ->groupByRaw("DATE({$column})")
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return $this->fillDates($rows, $start, $end);
    }

    private function dailyUsers(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $rows = User::query()
            ->selectRaw('DATE(last_active_at) as date, COUNT(*) as value')
            ->whereBetween('last_active_at', [$start, $end])
            ->groupByRaw('DATE(last_active_at)')
            ->orderBy('date')
            ->get()
            ->keyBy('date');

        return $this->fillDates($rows, $start, $end);
    }

    private function dailyAi(CarbonImmutable $start, CarbonImmutable $end): array
    {
        return ExerciseGenerationLog::query()
            ->selectRaw(
                "DATE(started_at) as date, COUNT(*) as total, ".
                "SUM(CASE WHEN status = 'failed' OR error IS NOT NULL THEN 1 ELSE 0 END) as failed"
            )
            ->whereBetween('started_at', [$start, $end])
            ->groupByRaw('DATE(started_at)')
            ->orderBy('date')
            ->get()
            ->map(fn ($row) => [
                'label' => $row->date,
                'value' => (int) $row->total,
                'secondary' => (int) $row->failed,
            ])
            ->all();
    }

    private function fillDates($rows, CarbonImmutable $start, CarbonImmutable $end): array
    {
        $days = min(366, $start->diffInDays($end) + 1);

        return collect(range(0, $days - 1))->map(function (int $offset) use ($rows, $start) {
            $date = $start->addDays($offset)->toDateString();

            return ['label' => $date, 'value' => (int) ($rows->get($date)?->value ?? 0)];
        })->all();
    }

    private function topicPerformance(string $locale, bool $weakest): array
    {
        $query = DB::table('math_level_attempts')
            ->join('math_levels', 'math_levels.id', '=', 'math_level_attempts.level_id')
            ->leftJoin('math_level_translations', function ($join) use ($locale) {
                $join->on('math_level_translations.math_level_id', '=', 'math_levels.id')
                    ->where('math_level_translations.locale', '=', $locale);
            })
            ->selectRaw(
                "COALESCE(math_level_translations.topic, math_levels.slug) as label, ".
                'COUNT(math_level_attempts.id) as attempts, AVG(math_level_attempts.score) as score'
            )
            ->whereNotNull('math_level_attempts.finished_at')
            ->groupBy('label');

        $weakest
            ? $query->orderBy('score')->orderByDesc('attempts')
            : $query->orderByDesc('attempts')->orderByDesc('score');

        return $query->limit(10)->get()->map(fn ($row) => [
            'label' => $row->label,
            'value' => $weakest ? round((float) $row->score, 1) : (int) $row->attempts,
            'secondary' => $weakest ? (int) $row->attempts : round((float) $row->score, 1),
        ])->all();
    }
}
