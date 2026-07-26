<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreCmsFormulaRequest;
use App\Http\Requests\Cms\StoreCmsUserRequest;
use App\Http\Requests\Cms\UpdateCmsLevelPromptRequest;
use App\Http\Requests\Cms\UpdateCmsLevelRequest;
use App\Http\Requests\Cms\UpdateCmsFormulaRequest;
use App\Http\Requests\Cms\UpdateCmsUserRequest;
use App\Models\ExerciseGenerationLog;
use App\Models\Formula;
use App\Models\FormulaStep;
use App\Models\FormulaTranslation;
use App\Models\MathLevel;
use App\Models\MathLevelPrompt;
use App\Models\MathPath;
use App\Models\User;
use App\Services\Cms\MockMathLevelPreviewService;
use App\Services\Cms\AuditService;
use App\Services\Cms\ContentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class CmsController extends Controller
{
    private const LOCALES = ['en', 'hy', 'ru', 'fr'];
    private const PREVIEW_SOURCE = 'preview';

    public function __construct(
        private readonly AuditService $audit,
        private readonly ContentVersionService $versions,
    ) {
    }

    public function overview(): JsonResponse
    {
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $usersCount = User::count();
        $adminsCount = User::where('role', User::ROLE_ADMIN)->count();
        $lecturersCount = User::where('role', User::ROLE_LECTURER)->count();
        $studentsCount = User::where('role', User::ROLE_STUDENT)->count();

        $formulasCount = Formula::count();
        $pathsCount = MathPath::count();
        $levelsCount = MathLevel::count();

        $aiLogs = ExerciseGenerationLog::query()
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $generationCount = (clone $aiLogs)->count();
        $failedGenerationCount = (clone $aiLogs)
            ->where(function ($query) {
                $query
                    ->whereNotNull('error')
                    ->orWhere('status', 'failed');
            })
            ->count();
        $successfulGenerationCount = (clone $aiLogs)
            ->whereNotNull('finished_at')
            ->whereNull('error')
            ->where('status', '!=', 'failed')
            ->count();
        $averageDurationSeconds = (int) round(
            (clone $aiLogs)
                ->whereNotNull('started_at')
                ->whereNotNull('finished_at')
                ->get(['started_at', 'finished_at'])
                ->avg(fn (ExerciseGenerationLog $log) => $log->started_at->diffInSeconds($log->finished_at))
                ?? 0
        );
        $processingGenerationCount = max(0, $generationCount - $successfulGenerationCount - $failedGenerationCount);
        $documentGenerationCount = (clone $aiLogs)
            ->whereNotNull('document_id')
            ->distinct('document_id')
            ->count('document_id');
        $successRate = $generationCount > 0
            ? (int) round(($successfulGenerationCount / $generationCount) * 100)
            : 0;

        $recentUsers = User::query()
            ->select(['id', 'name', 'email', 'role', 'created_at'])
            ->latest('id')
            ->limit(6)
            ->get();

        $recentFormulas = Formula::query()
            ->with('translations')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (Formula $formula) => $this->transformFormula($formula, request('locale', config('app.locale', 'en'))));

        return response()->json([
            'stats' => [
                'users' => $usersCount,
                'admins' => $adminsCount,
                'lecturers' => $lecturersCount,
                'students' => $studentsCount,
                'formulas' => $formulasCount,
                'paths' => $pathsCount,
                'levels' => $levelsCount,
            ],
            'ai_usage' => [
                'percent' => $successRate,
                'generations' => $generationCount,
                'successful' => $successfulGenerationCount,
                'documents' => $documentGenerationCount,
                'failed' => $failedGenerationCount,
                'processing' => $processingGenerationCount,
                'average_duration_seconds' => $averageDurationSeconds,
                'period_label' => 'This month',
                'items' => [
                    ['label' => 'Failed', 'value' => $failedGenerationCount],
                    ['label' => 'Processing', 'value' => $processingGenerationCount],
                    ['label' => 'Avg sec', 'value' => $averageDurationSeconds],
                ],
            ],
            'recent_users' => $recentUsers,
            'recent_formulas' => $recentFormulas,
        ]);
    }

    public function listUsers(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $query = User::query()->select(['id', 'name', 'email', 'role', 'created_at']);

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('role', 'like', "%{$search}%");
            });
        }

        $users = $query->latest('id')->paginate($perPage);

        return response()->json([
            'items' => $users->items(),
            'pagination' => $this->pagination($users),
        ]);
    }

    public function listFormulaCategories(): JsonResponse
    {
        $categories = Formula::query()
            ->select('category')
            ->whereNotNull('category')
            ->distinct()
            ->orderBy('category')
            ->pluck('category')
            ->filter(fn ($category) => trim((string) $category) !== '')
            ->values();

        return response()->json([
            'items' => $categories,
        ]);
    }

    public function listGenerationLogs(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['nullable', 'string', 'max:20'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:50'],
        ]);

        $perPage = max(5, min(50, (int) ($validated['per_page'] ?? 10)));

        $query = ExerciseGenerationLog::query()
            ->with([
                'user:id,name,email',
                'document:id,title,status,language',
            ]);

        if (!empty($validated['status'])) {
            $query->where('status', $validated['status']);
        }

        if (!empty($validated['user_id'])) {
            $query->where('user_id', (int) $validated['user_id']);
        }

        if (!empty($validated['date_from'])) {
            $query->where('started_at', '>=', $validated['date_from'].' 00:00:00');
        }

        if (!empty($validated['date_to'])) {
            $query->where('started_at', '<=', $validated['date_to'].' 23:59:59');
        }

        $logs = $query->latest('started_at')->latest('id')->paginate($perPage);

        return response()->json([
            'items' => collect($logs->items())
                ->map(fn (ExerciseGenerationLog $log) => $this->transformGenerationLog($log))
                ->values(),
            'pagination' => $this->pagination($logs),
        ]);
    }

    public function showGenerationLog(ExerciseGenerationLog $generationLog): JsonResponse
    {
        $generationLog->load([
            'user:id,name,email',
            'document:id,title,status,language',
        ]);

        return response()->json([
            'item' => $this->transformGenerationLog($generationLog, true),
        ]);
    }

    public function listFormulaExplanations(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $locale = $this->normalizeLocale((string) $request->query('locale', config('app.locale', 'en')));
        $cacheFilter = (string) $request->query('cache', 'all');
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $query = Formula::query()
            ->with([
                'translations',
                'steps' => fn ($stepsQuery) => $stepsQuery->where('locale', $locale),
            ])
            ->withCount('steps');

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('category', 'like', "%{$search}%")
                    ->orWhere('formula', 'like', "%{$search}%")
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        match ($cacheFilter) {
            'cached' => $query->whereHas('steps', fn ($stepsQuery) => $stepsQuery->where('locale', $locale)),
            'missing' => $query->whereDoesntHave('steps', fn ($stepsQuery) => $stepsQuery->where('locale', $locale)),
            'cached_any' => $query->has('steps'),
            'missing_any' => $query->doesntHave('steps'),
            default => null,
        };

        $formulas = $query->latest('id')->paginate($perPage);

        return response()->json([
            'items' => collect($formulas->items())
                ->map(fn (Formula $formula) => $this->transformFormulaExplanation($formula, $locale))
                ->values(),
            'pagination' => $this->pagination($formulas),
        ]);
    }

    public function showFormulaExplanation(Request $request, Formula $formula): JsonResponse
    {
        $locale = $this->normalizeLocale((string) $request->query('locale', config('app.locale', 'en')));

        $formula
            ->load([
                'translations',
                'steps' => fn ($stepsQuery) => $stepsQuery->where('locale', $locale),
            ])
            ->loadCount('steps');

        return response()->json([
            'item' => $this->transformFormulaExplanation($formula, $locale),
        ]);
    }

    public function updateFormulaExplanation(Request $request, Formula $formula): JsonResponse
    {
        $validated = $request->validate([
            'locale' => ['required', 'string', 'in:'.implode(',', self::LOCALES)],
            'steps_json' => ['present', 'array'],
            'steps_json.*.latex' => ['nullable', 'string'],
            'steps_json.*.note' => ['nullable', 'string'],
        ]);

        $locale = $this->normalizeLocale((string) $validated['locale']);
        $steps = collect($validated['steps_json'])
            ->map(function (array $step): array {
                return [
                    'latex' => trim((string) ($step['latex'] ?? '')),
                    'note' => trim((string) ($step['note'] ?? '')),
                ];
            })
            ->filter(fn (array $step): bool => $step['latex'] !== '' || $step['note'] !== '')
            ->values()
            ->all();

        $formulaStep = FormulaStep::updateOrCreate(
            [
                'formula_id' => $formula->id,
                'locale' => $locale,
            ],
            [
                'steps_json' => $steps,
            ]
        );
        $this->versions->capture(
            $formula,
            [],
            ['locale' => $locale, 'steps_json' => $steps],
            'Formula explanation updated'
        );
        $this->audit->record(
            'formula.explanation_updated',
            $formula,
            [],
            ['locale' => $locale, 'steps_json' => $steps]
        );

        $formula->load('translations');
        $formula->loadCount('steps');
        $formula->setRelation('steps', collect([$formulaStep]));

        return response()->json([
            'item' => $this->transformFormulaExplanation($formula, $locale),
        ]);
    }

    public function storeUser(StoreCmsUserRequest $request): JsonResponse
    {
        if ($request->validated('role') !== User::ROLE_STUDENT) {
            abort_unless($request->user()->hasCmsPermission('users.roles.manage'), 403);
        }
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->input('status', 'active'),
            'locale' => $request->input('locale', config('app.locale', 'en')),
            'country_id' => $request->input('country_id'),
            'password' => Hash::make($request->string('password')->toString()),
        ]);
        $this->audit->record(
            'user.created',
            $user,
            [],
            $this->audit->modelSnapshot($user)
        );

        return response()->json([
            'item' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
        ], 201);
    }

    public function updateUser(UpdateCmsUserRequest $request, User $user): JsonResponse
    {
        $before = $this->audit->modelSnapshot($user);
        $payload = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
            'status' => $request->input('status', $user->status),
            'locale' => $request->input('locale', $user->locale),
            'country_id' => $request->input('country_id'),
        ];

        if ((int) $request->user()->id === (int) $user->id && $payload['role'] !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access.',
            ]);
        }
        if ($payload['role'] !== $user->role) {
            abort_unless($request->user()->hasCmsPermission('users.roles.manage'), 403);
        }

        $user->update($payload);
        $this->audit->record(
            'user.updated',
            $user,
            $before,
            $this->audit->modelSnapshot($user->fresh())
        );

        return response()->json([
            'item' => $user->fresh()->only(['id', 'name', 'email', 'role', 'created_at']),
        ]);
    }

    public function destroyUser(Request $request, User $user): JsonResponse
    {
        if ((int) $request->user()->id === (int) $user->id) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account here.',
            ]);
        }

        $before = $this->audit->modelSnapshot($user);
        $user->delete();
        $this->audit->record('user.deleted', $user, $before, ['deleted' => true]);

        return response()->json(['ok' => true]);
    }

    public function updateCurrentProfile(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id),
            ],
        ]);

        $user->fill($validated);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        return response()->json([
            'item' => $this->currentUserPayload($user->fresh()),
        ]);
    }

    public function updateCurrentPassword(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', Password::defaults(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json(['ok' => true]);
    }

    public function listFormulas(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));
        $locale = $this->normalizeLocale((string) $request->query('locale', config('app.locale', 'en')));
        $perPage = max(5, min(50, (int) $request->query('per_page', 10)));

        $query = Formula::query()->with('translations');

        if ($search !== '') {
            $query->where(function ($inner) use ($search) {
                $inner
                    ->where('category', 'like', "%{$search}%")
                    ->orWhere('formula', 'like', "%{$search}%")
                    ->orWhereHas('translations', function ($translationQuery) use ($search) {
                        $translationQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        $formulas = $query->latest('id')->paginate($perPage);

        return response()->json([
            'items' => collect($formulas->items())
                ->map(fn (Formula $formula) => $this->transformFormula($formula, $locale))
                ->values(),
            'pagination' => $this->pagination($formulas),
        ]);
    }

    public function storeFormula(StoreCmsFormulaRequest $request): JsonResponse
    {
        $translations = (array) $request->input('translations', []);
        $this->ensureHasTranslationName($translations);

        $formula = DB::transaction(function () use ($request, $translations) {
            $formula = Formula::create([
                'category' => $request->string('category')->toString(),
                'formula' => $request->string('formula')->toString(),
                'example' => $request->filled('example') ? $request->string('example')->toString() : null,
            ]);

            $this->syncTranslations($formula, $translations);

            return $formula;
        });

        $formula->load('translations');
        $snapshot = array_merge(
            $this->audit->modelSnapshot($formula),
            ['translations' => $formula->translations->toArray()]
        );
        $this->versions->capture($formula, [], $snapshot, 'Formula created');
        $this->audit->record('formula.created', $formula, [], $snapshot);

        return response()->json([
            'item' => $this->transformFormula($formula, $request->query('locale', config('app.locale', 'en'))),
        ], 201);
    }

    public function updateFormula(UpdateCmsFormulaRequest $request, Formula $formula): JsonResponse
    {
        $formula->load('translations');
        $before = array_merge(
            $this->audit->modelSnapshot($formula),
            ['translations' => $formula->translations->toArray()]
        );
        $translations = (array) $request->input('translations', []);
        $this->ensureHasTranslationName($translations);

        DB::transaction(function () use ($request, $formula, $translations): void {
            $formula->update([
                'category' => $request->string('category')->toString(),
                'formula' => $request->string('formula')->toString(),
                'example' => $request->filled('example') ? $request->string('example')->toString() : null,
            ]);

            $this->syncTranslations($formula, $translations);
        });

        $formula->load('translations');
        $after = array_merge(
            $this->audit->modelSnapshot($formula),
            ['translations' => $formula->translations->toArray()]
        );
        $this->versions->capture($formula, $before, $after);
        $this->audit->record('formula.updated', $formula, $before, $after);

        return response()->json([
            'item' => $this->transformFormula($formula, $request->query('locale', config('app.locale', 'en'))),
        ]);
    }

    public function destroyFormula(Formula $formula): JsonResponse
    {
        $before = $this->audit->modelSnapshot($formula);
        $formula->delete();
        $this->audit->record('formula.deleted', $formula, $before, ['deleted' => true]);

        return response()->json(['ok' => true]);
    }

    public function listLevels(Request $request): JsonResponse
    {
        $locale = $this->normalizeLocale((string) $request->query('locale', config('app.locale', 'en')));

        $paths = MathPath::query()
            ->with([
                'translations',
                'levels' => function ($query) {
                    $query
                        ->with(['translations', 'prompt'])
                        ->withCount('attempts')
                        ->orderBy('order');
                },
            ])
            ->orderBy('order')
            ->get();

        return response()->json([
            'items' => $paths->map(function (MathPath $path) use ($locale) {
                $pathTranslation = $path->resolveTranslation([$locale, config('app.fallback_locale', 'en'), 'en']);

                return [
                    'id' => $path->id,
                    'slug' => $path->slug,
                    'title' => $pathTranslation?->title ?? $path->slug,
                    'description' => $pathTranslation?->description,
                    'active' => (bool) $path->active,
                    'order' => (int) $path->order,
                    'levels' => $path->levels->map(function (MathLevel $level) use ($locale) {
                        $levelTranslation = $level->resolveTranslation([$locale, config('app.fallback_locale', 'en'), 'en']);
                        $prompt = $level->prompt;

                        return [
                            'id' => $level->id,
                            'path_id' => $level->path_id,
                            'slug' => $level->slug,
                            'title' => $levelTranslation?->title ?? $level->slug,
                            'topic' => $levelTranslation?->topic ?? '',
                            'description' => $levelTranslation?->description,
                            'difficulty' => $level->difficulty,
                            'order' => (int) $level->order,
                            'passing_score' => (int) $level->passing_score,
                            'xp_reward' => (int) $level->xp_reward,
                            'time_limit' => $level->time_limit === null ? null : (int) $level->time_limit,
                            'active' => (bool) $level->active,
                            'attempts_count' => (int) $level->attempts_count,
                            'prompt' => $prompt ? [
                                'id' => $prompt->id,
                                'prompt' => $prompt->prompt,
                                'question_count' => (int) $prompt->question_count,
                                'options_count' => (int) $prompt->options_count,
                                'locale' => $prompt->locale,
                            ] : null,
                        ];
                    })->values(),
                ];
            })->values(),
        ]);
    }

    public function updateLevel(UpdateCmsLevelRequest $request, MathLevel $level): JsonResponse
    {
        $before = $this->audit->modelSnapshot($level);
        $level->update([
            'difficulty' => $request->string('difficulty')->toString(),
            'passing_score' => $request->integer('passing_score'),
            'xp_reward' => $request->integer('xp_reward'),
            'time_limit' => $request->filled('time_limit') ? $request->integer('time_limit') : null,
            'active' => $request->boolean('active'),
        ]);
        $after = $this->audit->modelSnapshot($level->fresh());
        $this->versions->capture($level, $before, $after);
        $this->audit->record('level.updated', $level, $before, $after);

        return response()->json([
            'item' => [
                'id' => $level->id,
                'difficulty' => $level->difficulty,
                'passing_score' => (int) $level->passing_score,
                'xp_reward' => (int) $level->xp_reward,
                'time_limit' => $level->time_limit === null ? null : (int) $level->time_limit,
                'active' => (bool) $level->active,
            ],
        ]);
    }

    public function updateLevelPrompt(UpdateCmsLevelPromptRequest $request, MathLevel $level): JsonResponse
    {
        $before = $level->prompt?->toArray() ?? [];
        $prompt = $level->prompt()->updateOrCreate(
            ['level_id' => $level->id],
            [
                'prompt' => $request->string('prompt')->toString(),
                'question_count' => $request->integer('question_count'),
                'options_count' => $request->integer('options_count'),
                'locale' => $request->string('locale')->toString(),
            ]
        );
        $this->versions->capture(
            $level,
            ['prompt' => $before],
            ['prompt' => $prompt->toArray()],
            'Level generation prompt updated'
        );
        $this->audit->record(
            'level.prompt_updated',
            $level,
            ['prompt' => $before],
            ['prompt' => $prompt->toArray()]
        );

        return response()->json([
            'item' => [
                'id' => $prompt->id,
                'level_id' => $prompt->level_id,
                'prompt' => $prompt->prompt,
                'question_count' => (int) $prompt->question_count,
                'options_count' => (int) $prompt->options_count,
                'locale' => $prompt->locale,
            ],
        ]);
    }

    public function previewLevelPrompt(
        UpdateCmsLevelPromptRequest $request,
        MathLevel $level,
        MockMathLevelPreviewService $previewService
    ): JsonResponse {
        $level->loadMissing([
            'translations',
            'path.translations',
        ]);

        $previewPrompt = new MathLevelPrompt([
            'level_id' => $level->id,
            'prompt' => $request->string('prompt')->toString(),
            'question_count' => $request->integer('question_count'),
            'options_count' => $request->integer('options_count'),
            'locale' => $request->string('locale')->toString(),
        ]);
        $previewPrompt->setRelation('level', $level);

        $startedAt = now();
        $preview = $previewService->generate($previewPrompt);
        $finishedAt = now();

        $log = ExerciseGenerationLog::create([
            'user_id' => (int) $request->user()->id,
            'document_id' => null,
            'source' => self::PREVIEW_SOURCE,
            'status' => 'completed',
            'prompt' => $preview['logged_prompt'],
            'request_json' => json_encode($preview['request_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'response_raw' => $preview['response_raw'],
            'parsed_json' => json_encode($preview['parsed_payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            'error' => null,
            'started_at' => $startedAt,
            'finished_at' => $finishedAt,
        ]);
        $log->load('user:id,name,email');

        return response()->json([
            'item' => [
                'notice' => $preview['notice'],
                'source' => self::PREVIEW_SOURCE,
                'status' => 'completed',
                'preview' => $preview['parsed_payload'],
                'response_raw' => $preview['response_raw'],
                'logged_generation' => $this->transformGenerationLog($log, true),
            ],
        ]);
    }

    private function syncTranslations(Formula $formula, array $translations): void
    {
        $formula->translations()->delete();

        foreach (self::LOCALES as $locale) {
            $entry = $translations[$locale] ?? [];
            $name = trim((string) ($entry['name'] ?? ''));
            $description = array_key_exists('description', (array) $entry)
                ? trim((string) ($entry['description'] ?? ''))
                : null;

            if ($name === '') {
                continue;
            }

            FormulaTranslation::create([
                'formula_id' => $formula->id,
                'locale' => $locale,
                'name' => $name,
                'description' => $description !== '' ? $description : null,
            ]);
        }
    }

    private function ensureHasTranslationName(array $translations): void
    {
        $hasName = collect(self::LOCALES)
            ->contains(function (string $locale) use ($translations): bool {
                return trim((string) ($translations[$locale]['name'] ?? '')) !== '';
            });

        if (!$hasName) {
            throw ValidationException::withMessages([
                'translations' => 'Add at least one translation name (en / hy / ru / fr).',
            ]);
        }
    }

    private function transformFormula(Formula $formula, string $preferredLocale): array
    {
        $translations = $formula->translations->keyBy('locale');
        $locale = $this->normalizeLocale($preferredLocale);

        $selected = $translations->get($locale)
            ?? $translations->get(config('app.fallback_locale', 'en'))
            ?? $translations->first();

        return [
            'id' => $formula->id,
            'category' => $formula->category,
            'formula' => $formula->formula,
            'example' => $formula->example,
            'name' => $selected?->name,
            'description' => $selected?->description,
            'translations' => collect(self::LOCALES)->mapWithKeys(function (string $itemLocale) use ($translations) {
                $item = $translations->get($itemLocale);
                return [
                    $itemLocale => [
                        'name' => $item?->name ?? '',
                        'description' => $item?->description ?? '',
                    ],
                ];
            }),
            'created_at' => $formula->created_at,
            'updated_at' => $formula->updated_at,
        ];
    }

    private function transformFormulaExplanation(Formula $formula, string $preferredLocale): array
    {
        $base = $this->transformFormula($formula, $preferredLocale);
        $step = $formula->steps->first();

        return array_merge($base, [
            'locale' => $this->normalizeLocale($preferredLocale),
            'steps_json' => $step?->steps_json ?? [],
            'steps_count' => is_array($step?->steps_json) ? count($step->steps_json) : 0,
            'locales_count' => (int) ($formula->steps_count ?? 0),
            'steps_updated_at' => $step?->updated_at,
        ]);
    }

    private function transformGenerationLog(ExerciseGenerationLog $log, bool $includePayloads = false): array
    {
        $durationSeconds = $log->started_at && $log->finished_at
            ? $log->started_at->diffInSeconds($log->finished_at)
            : null;

        $item = [
            'id' => $log->id,
            'status' => $log->status,
            'source' => $log->source ?? 'runtime',
            'user' => $log->user ? [
                'id' => $log->user->id,
                'name' => $log->user->name,
                'email' => $log->user->email,
            ] : null,
            'document' => $log->document ? [
                'id' => $log->document->id,
                'title' => $log->document->title,
                'status' => $log->document->status,
                'language' => $log->document->language,
            ] : null,
            'started_at' => $log->started_at,
            'finished_at' => $log->finished_at,
            'duration_seconds' => $durationSeconds,
            'error_excerpt' => $log->error ? Str::limit($log->error, 120) : null,
        ];

        if (!$includePayloads) {
            return $item;
        }

        // Admin-only for now. If admin access ever expands beyond technical staff,
        // request_json / response_raw must be redacted for student-identifying data
        // before these payloads remain visible in CMS detail views.
        return array_merge($item, [
            'prompt' => $log->prompt,
            'request_json' => $log->request_json,
            'response_raw' => $log->response_raw,
            'parsed_json' => $log->parsed_json,
            'error' => $log->error,
            'created_at' => $log->created_at,
            'updated_at' => $log->updated_at,
        ]);
    }

    private function pagination(LengthAwarePaginator $paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }

    private function currentUserPayload(User $user): array
    {
        return $user->only([
            'id',
            'name',
            'email',
            'role',
            'email_verified_at',
            'created_at',
            'updated_at',
        ]);
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return in_array($locale, self::LOCALES, true)
            ? $locale
            : 'en';
    }
}
