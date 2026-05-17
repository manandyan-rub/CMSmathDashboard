<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\StoreCmsFormulaRequest;
use App\Http\Requests\Cms\StoreCmsUserRequest;
use App\Http\Requests\Cms\UpdateCmsLevelPromptRequest;
use App\Http\Requests\Cms\UpdateCmsLevelRequest;
use App\Http\Requests\Cms\UpdateCmsFormulaRequest;
use App\Http\Requests\Cms\UpdateCmsUserRequest;
use App\Models\Formula;
use App\Models\FormulaTranslation;
use App\Models\MathLevel;
use App\Models\MathPath;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CmsController extends Controller
{
    private const LOCALES = ['en', 'hy', 'ru', 'fr'];

    public function overview(): JsonResponse
    {
        $usersCount = User::count();
        $adminsCount = User::where('role', User::ROLE_ADMIN)->count();
        $lecturersCount = User::where('role', User::ROLE_LECTURER)->count();
        $studentsCount = User::where('role', User::ROLE_STUDENT)->count();

        $formulasCount = Formula::count();
        $pathsCount = MathPath::count();
        $levelsCount = MathLevel::count();

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

    public function storeUser(StoreCmsUserRequest $request): JsonResponse
    {
        $user = User::create([
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
            'password' => Hash::make($request->string('password')->toString()),
        ]);

        return response()->json([
            'item' => $user->only(['id', 'name', 'email', 'role', 'created_at']),
        ], 201);
    }

    public function updateUser(UpdateCmsUserRequest $request, User $user): JsonResponse
    {
        $payload = [
            'name' => $request->string('name')->toString(),
            'email' => $request->string('email')->toString(),
            'role' => $request->string('role')->toString(),
        ];

        $password = trim((string) $request->input('password', ''));
        if ($password !== '') {
            $payload['password'] = Hash::make($password);
        }

        if ((int) $request->user()->id === (int) $user->id && $payload['role'] !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages([
                'role' => 'You cannot remove your own admin access.',
            ]);
        }

        $user->update($payload);

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

        $user->delete();

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

        return response()->json([
            'item' => $this->transformFormula($formula, $request->query('locale', config('app.locale', 'en'))),
        ], 201);
    }

    public function updateFormula(UpdateCmsFormulaRequest $request, Formula $formula): JsonResponse
    {
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

        return response()->json([
            'item' => $this->transformFormula($formula, $request->query('locale', config('app.locale', 'en'))),
        ]);
    }

    public function destroyFormula(Formula $formula): JsonResponse
    {
        $formula->delete();

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
        $level->update([
            'difficulty' => $request->string('difficulty')->toString(),
            'passing_score' => $request->integer('passing_score'),
            'xp_reward' => $request->integer('xp_reward'),
            'time_limit' => $request->filled('time_limit') ? $request->integer('time_limit') : null,
            'active' => $request->boolean('active'),
        ]);

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
        $prompt = $level->prompt()->updateOrCreate(
            ['level_id' => $level->id],
            [
                'prompt' => $request->string('prompt')->toString(),
                'question_count' => $request->integer('question_count'),
                'options_count' => $request->integer('options_count'),
                'locale' => $request->string('locale')->toString(),
            ]
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

    private function normalizeLocale(string $locale): string
    {
        $locale = strtolower(trim($locale));

        return in_array($locale, self::LOCALES, true)
            ? $locale
            : 'en';
    }
}
