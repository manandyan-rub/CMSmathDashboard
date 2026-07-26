<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\GameDefinition;
use App\Models\MathLevel;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LearningAnalyticsController extends Controller
{
    public function level(Request $request, MathLevel $level): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('levels.view'), 403);
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]);
        $attempts = $level->attempts();
        if (!empty($validated['date_from'])) {
            $attempts->where('started_at', '>=', $validated['date_from'].' 00:00:00');
        }
        if (!empty($validated['date_to'])) {
            $attempts->where('started_at', '<=', $validated['date_to'].' 23:59:59');
        }
        $attemptIds = (clone $attempts)->select('id');
        $summary = (clone $attempts)->selectRaw(
            'COUNT(*) as attempts, '.
            'SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) as completions, '.
            'SUM(CASE WHEN passed = 1 THEN 1 ELSE 0 END) as passes, '.
            'AVG(score) as average_score, AVG(hints_used) as average_hints, '.
            'AVG(duration_seconds) as average_duration'
        )->first();
        $learnerAverages = (clone $attempts)
            ->selectRaw('user_id, COUNT(*) as attempts')
            ->groupBy('user_id')
            ->get();

        $hardestQuestions = DB::table('math_level_attempt_questions')
            ->join(
                'math_level_attempt_answers',
                'math_level_attempt_answers.attempt_question_id',
                '=',
                'math_level_attempt_questions.id'
            )
            ->whereIn('math_level_attempt_questions.attempt_id', $attemptIds)
            ->whereNotNull('math_level_attempt_questions.source_question_id')
            ->selectRaw(
                'math_level_attempt_questions.source_question_id as id, '.
                'MIN(math_level_attempt_questions.question) as question, '.
                'COUNT(*) as answers, '.
                'AVG(CASE WHEN math_level_attempt_answers.is_correct = 1 THEN 1 ELSE 0 END) * 100 as correctness'
            )
            ->groupBy('math_level_attempt_questions.source_question_id')
            ->orderBy('correctness')
            ->limit(15)
            ->get();

        $mistakes = DB::table('math_level_attempt_answers')
            ->join(
                'math_level_attempt_question_options',
                'math_level_attempt_question_options.id',
                '=',
                'math_level_attempt_answers.attempt_option_id'
            )
            ->join(
                'math_level_attempt_questions',
                'math_level_attempt_questions.id',
                '=',
                'math_level_attempt_answers.attempt_question_id'
            )
            ->whereIn('math_level_attempt_questions.attempt_id', $attemptIds)
            ->where('math_level_attempt_answers.is_correct', false)
            ->selectRaw('math_level_attempt_question_options.option_text as label, COUNT(*) as value')
            ->groupBy('math_level_attempt_question_options.option_text')
            ->orderByDesc('value')
            ->limit(15)
            ->get();

        $attemptCount = (int) ($summary->attempts ?? 0);

        return response()->json([
            'summary' => [
                'attempts' => $attemptCount,
                'completions' => (int) ($summary->completions ?? 0),
                'pass_rate' => $attemptCount
                    ? round(((int) $summary->passes / $attemptCount) * 100, 1)
                    : 0,
                'average_score' => round((float) ($summary->average_score ?? 0), 1),
                'average_attempts' => round((float) ($learnerAverages->avg('attempts') ?? 0), 1),
                'average_hints' => round((float) ($summary->average_hints ?? 0), 1),
                'average_duration' => round((float) ($summary->average_duration ?? 0), 1),
            ],
            'hardest_questions' => $hardestQuestions,
            'common_mistakes' => $mistakes,
        ]);
    }

    public function studentProgress(Request $request, User $user): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('users.view'), 403);
        $validated = $request->validate([
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'path_id' => ['nullable', 'integer', 'exists:math_paths,id'],
            'level_id' => ['nullable', 'integer', 'exists:math_levels,id'],
            'topic' => ['nullable', 'string', 'max:100'],
            'difficulty' => ['nullable', 'string', 'max:30'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $query = $user->levelAttempts()
            ->with(['level.translations', 'answers'])
            ->whereNotNull('finished_at');
        if (!empty($validated['date_from'])) {
            $query->where('finished_at', '>=', $validated['date_from'].' 00:00:00');
        }
        if (!empty($validated['date_to'])) {
            $query->where('finished_at', '<=', $validated['date_to'].' 23:59:59');
        }
        if (!empty($validated['level_id'])) {
            $query->where('level_id', $validated['level_id']);
        }
        if (!empty($validated['path_id'])) {
            $query->whereHas('level', fn ($level) => $level->where('path_id', $validated['path_id']));
        }
        if (!empty($validated['difficulty'])) {
            $query->whereHas('level', fn ($level) => $level->where('difficulty', $validated['difficulty']));
        }
        if (!empty($validated['topic'])) {
            $query->whereHas('level.translations', fn ($translation) => $translation
                ->where('topic', $validated['topic']));
        }
        $summaryQuery = clone $query;
        $attempts = (clone $query)->latest('finished_at')->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'summary' => [
                'attempts' => $attempts->total(),
                'average_score' => round((float) ((clone $summaryQuery)->avg('score') ?? 0), 1),
                'correct_answers' => (int) (clone $summaryQuery)->sum('correct_answers'),
                'incorrect_answers' => (int) (clone $summaryQuery)->sum('wrong_answers'),
                'hints_used' => (int) (clone $summaryQuery)->sum('hints_used'),
            ],
            'items' => $attempts->items(),
            'pagination' => [
                'page' => $attempts->currentPage(),
                'last_page' => $attempts->lastPage(),
                'total' => $attempts->total(),
            ],
            'streak_history' => DB::table('streak_history')
                ->where('user_id', $user->id)
                ->latest('streak_date')
                ->limit(90)
                ->get(),
        ]);
    }

    public function game(Request $request, GameDefinition $game): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('games.view'), 403);
        $query = $game->sessions();
        $summary = (clone $query)->selectRaw(
            'COUNT(*) as sessions, '.
            'SUM(CASE WHEN completed = 1 THEN 1 ELSE 0 END) as completed, '.
            'AVG(score) as average_score, AVG(duration_seconds) as average_duration, '.
            'COUNT(DISTINCT user_id) as active_players'
        )->first();
        $sessions = (int) $summary->sessions;
        $mistakes = (clone $query)
            ->whereNotNull('mistakes')
            ->latest()
            ->limit(500)
            ->get(['mistakes'])
            ->flatMap(fn ($session) => $session->mistakes ?? [])
            ->countBy()
            ->sortDesc()
            ->take(15)
            ->map(fn ($value, $label) => compact('label', 'value'))
            ->values();

        return response()->json([
            'summary' => [
                'sessions' => (int) $summary->sessions,
                'completion_rate' => $sessions
                    ? round(((int) $summary->completed / $sessions) * 100, 1)
                    : 0,
                'average_score' => round((float) $summary->average_score, 1),
                'average_duration' => round((float) $summary->average_duration, 1),
                'quit_rate' => $sessions
                    ? round((1 - ((int) $summary->completed / $sessions)) * 100, 1)
                    : 0,
                'active_players' => (int) $summary->active_players,
            ],
            'common_mistakes' => $mistakes,
        ]);
    }

    public function hallOfFame(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('gamification.view'), 403);

        return response()->json([
            'items' => DB::table('math_user_profiles')
                ->join('users', 'users.id', '=', 'math_user_profiles.user_id')
                ->where('users.status', 'active')
                ->select([
                    'users.id',
                    'users.name',
                    'users.avatar_path',
                    'math_user_profiles.total_xp',
                    'math_user_profiles.current_rank',
                    'math_user_profiles.streak_days',
                    'math_user_profiles.total_stars',
                ])
                ->orderByDesc('math_user_profiles.total_xp')
                ->limit(100)
                ->get(),
        ]);
    }
}
