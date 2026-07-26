<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\AdjustUserXpRequest;
use App\Http\Requests\Cms\ResetUserProgressRequest;
use App\Http\Requests\Cms\UpdateLecturerRequest;
use App\Http\Requests\Cms\UpdateUserPermissionsRequest;
use App\Http\Requests\Cms\UserAdminActionRequest;
use App\Http\Requests\Cms\UserIndexRequest;
use App\Models\AuditLog;
use App\Models\ContentEvent;
use App\Models\ExerciseGenerationLog;
use App\Models\Permission;
use App\Models\User;
use App\Services\Cms\AuditService;
use App\Services\Cms\UserAdministrationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserAdministrationController extends Controller
{
    public function index(UserIndexRequest $request): JsonResponse
    {
        Gate::authorize('viewAny', User::class);
        $validated = $request->validated();
        $query = User::query()
            ->select([
                'id',
                'name',
                'email',
                'role',
                'status',
                'locale',
                'country_id',
                'avatar_path',
                'email_verified_at',
                'last_login_at',
                'last_active_at',
                'created_at',
            ])
            ->with(['country:id,name,iso_code', 'mathProfile:user_id,total_xp,current_rank,streak_days'])
            ->withCount(['levelAttempts', 'exerciseDocuments', 'generationLogs']);

        if ($search = trim((string) ($validated['search'] ?? ''))) {
            $query->where(fn ($inner) => $inner
                ->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }
        foreach (['role', 'status', 'country_id', 'locale'] as $filter) {
            if (isset($validated[$filter])) {
                $query->where($filter, $validated[$filter]);
            }
        }
        if (!empty($validated['registered_from'])) {
            $query->whereDate('created_at', '>=', $validated['registered_from']);
        }
        if (!empty($validated['registered_to'])) {
            $query->whereDate('created_at', '<=', $validated['registered_to']);
        }
        if (!empty($validated['last_active_from'])) {
            $query->whereDate('last_active_at', '>=', $validated['last_active_from']);
        }
        if (array_key_exists('verified', $validated)) {
            $validated['verified']
                ? $query->whereNotNull('email_verified_at')
                : $query->whereNull('email_verified_at');
        }

        $sort = $validated['sort'] ?? 'created_at';
        $direction = ($validated['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $users = $query->orderBy($sort, $direction)->paginate($validated['per_page'] ?? 20);

        return response()->json([
            'items' => collect($users->items())->map(fn (User $user) => $this->listPayload($user)),
            'pagination' => [
                'page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'from' => $users->firstItem(),
                'to' => $users->lastItem(),
            ],
        ]);
    }

    public function show(Request $request, User $user): JsonResponse
    {
        Gate::authorize('view', $user);
        $user->load([
            'country',
            'mathProfile',
            'lecturerProfile',
            'permissions',
        ]);

        $recentAttempts = $user->levelAttempts()
            ->with('level.translations')
            ->withCount('answers')
            ->latest('started_at')
            ->limit(20)
            ->get();
        $progress = $user->levelProgress()
            ->with('level.translations')
            ->latest('last_attempted_at')
            ->paginate(20, ['*'], 'progress_page');

        $weakTopics = DB::table('math_level_attempts')
            ->join('math_levels', 'math_levels.id', '=', 'math_level_attempts.level_id')
            ->leftJoin('math_level_translations', function ($join) use ($request) {
                $join->on('math_level_translations.math_level_id', '=', 'math_levels.id')
                    ->where('math_level_translations.locale', '=', $request->query('locale', 'en'));
            })
            ->where('math_level_attempts.user_id', $user->id)
            ->selectRaw(
                'COALESCE(math_level_translations.topic, math_levels.slug) as topic, '.
                'COUNT(*) as attempts, AVG(math_level_attempts.score) as average_score'
            )
            ->groupBy('topic')
            ->orderBy('average_score')
            ->limit(10)
            ->get();

        $aiUsage = ExerciseGenerationLog::query()
            ->where('user_id', $user->id)
            ->selectRaw(
                'COUNT(*) as requests, '.
                "SUM(CASE WHEN status = 'failed' OR error IS NOT NULL THEN 1 ELSE 0 END) as failures, ".
                'SUM(COALESCE(input_tokens, 0) + COALESCE(output_tokens, 0)) as tokens, '.
                'SUM(COALESCE(estimated_cost, 0)) as estimated_cost, '.
                'AVG(latency_ms) as average_latency_ms'
            )
            ->first();

        $recentAudit = AuditLog::query()
            ->with('administrator:id,name,email')
            ->where('entity_type', $user->getMorphClass())
            ->where('entity_id', $user->id)
            ->latest()
            ->limit(30)
            ->get();

        return response()->json([
            'user' => array_merge($this->listPayload($user), [
                'email_verified_at' => $user->email_verified_at,
                'last_login_at' => $user->last_login_at,
                'suspended_at' => $user->suspended_at,
                'suspended_reason' => $user->suspended_reason,
                'permissions' => $user->cmsPermissionKeys(),
                'permission_overrides' => $user->permissions,
                'lecturer_profile' => $user->lecturerProfile,
            ]),
            'statistics' => [
                'attempts' => $user->levelAttempts()->count(),
                'completed_levels' => $user->levelProgress()->where('completed', true)->count(),
                'average_score' => round((float) ($user->levelAttempts()->avg('score') ?? 0), 1),
                'correct_answers' => (int) $user->levelAttempts()->sum('correct_answers'),
                'incorrect_answers' => (int) $user->levelAttempts()->sum('wrong_answers'),
                'documents' => $user->exerciseDocuments()->count(),
            ],
            'weak_topics' => $weakTopics,
            'recent_attempts' => $recentAttempts,
            'progress' => [
                'items' => $progress->items(),
                'page' => $progress->currentPage(),
                'last_page' => $progress->lastPage(),
                'total' => $progress->total(),
            ],
            'xp_history' => $user->xpLedgerEntries()->latest('occurred_at')->limit(50)->get(),
            'rewards' => DB::table('user_rewards')
                ->join('rewards', 'rewards.id', '=', 'user_rewards.reward_id')
                ->where('user_rewards.user_id', $user->id)
                ->select(['user_rewards.*', 'rewards.key', 'rewards.type'])
                ->latest('user_rewards.earned_at')
                ->limit(30)
                ->get(),
            'ai_usage' => $aiUsage,
            'documents' => $user->exerciseDocuments()->latest()->limit(20)->get(),
            'recent_activity' => ContentEvent::where('user_id', $user->id)->latest('occurred_at')->limit(30)->get(),
            'audit_history' => $recentAudit,
        ]);
    }

    public function action(
        UserAdminActionRequest $request,
        User $user,
        UserAdministrationService $service,
        AuditService $audit,
    ): JsonResponse {
        Gate::authorize('update', $user);
        $action = $request->validated('action');
        $reason = $request->validated('reason', 'Password reset requested by administrator.');

        if ($request->user()->is($user) && in_array($action, ['suspend', 'deactivate'], true)) {
            throw ValidationException::withMessages(['action' => 'You cannot disable your own account.']);
        }

        $message = match ($action) {
            'activate' => $this->applyStatus($service, $user, 'active', $reason, 'User activated.'),
            'suspend' => $this->applyStatus($service, $user, 'suspended', $reason, 'User suspended.'),
            'deactivate' => $this->applyStatus(
                $service,
                $user,
                'deactivated',
                $reason,
                'User deactivated.'
            ),
            'verify_email' => $this->applyEmailVerification($service, $user, $reason),
            'change_role' => $this->changeRole($request, $user, $service, $reason),
            'send_password_reset' => $this->sendPasswordReset($user, $audit),
        };

        return response()->json(['message' => $message, 'user' => $this->listPayload($user->fresh())]);
    }

    public function adjustXp(
        AdjustUserXpRequest $request,
        User $user,
        UserAdministrationService $service,
    ): JsonResponse {
        Gate::authorize('adjustXp', User::class);
        $entry = $service->adjustXp(
            $user,
            $request->validated('amount'),
            $request->validated('reason')
        );

        return response()->json(['message' => 'XP balance adjusted.', 'entry' => $entry]);
    }

    public function resetProgress(
        ResetUserProgressRequest $request,
        User $user,
        UserAdministrationService $service,
    ): JsonResponse {
        Gate::authorize('resetProgress', User::class);
        $reset = $service->resetProgress(
            $user,
            $request->validated('level_id'),
            $request->validated('reason')
        );

        return response()->json([
            'message' => 'Progress was reset without deleting attempt history.',
            'reset' => $reset,
        ]);
    }

    public function updateLecturer(
        UpdateLecturerRequest $request,
        User $user,
        UserAdministrationService $service,
    ): JsonResponse {
        abort_unless($user->role === User::ROLE_LECTURER, 422, 'This user is not a lecturer.');
        $profile = $service->updateLecturer($user, $request->validated());

        return response()->json(['message' => 'Lecturer settings updated.', 'profile' => $profile]);
    }

    public function permissions(
        UpdateUserPermissionsRequest $request,
        User $user,
        AuditService $audit,
    ): JsonResponse {
        $before = $user->cmsPermissionKeys();
        DB::transaction(function () use ($request, $user) {
            foreach ($request->validated('permissions') as $item) {
                $permission = Permission::where('key', $item['key'])->firstOrFail();
                if ($item['allowed'] === null) {
                    $user->permissions()->detach($permission->id);
                    continue;
                }
                $user->permissions()->syncWithoutDetaching([
                    $permission->id => [
                        'allowed' => $item['allowed'],
                        'granted_by' => $request->user()->id,
                    ],
                ]);
            }
        });
        $after = $user->fresh()->cmsPermissionKeys();
        $audit->record(
            'user.permissions_changed',
            $user,
            ['permissions' => $before],
            ['permissions' => $after],
            $request->validated('reason')
        );

        return response()->json(['message' => 'Permission overrides updated.', 'permissions' => $after]);
    }

    public function export(Request $request, User $user): StreamedResponse
    {
        Gate::authorize('view', $user);
        abort_unless($request->user()->hasCmsPermission('exports.create'), 403);
        $payload = [
            'profile' => $this->listPayload($user->load(['country', 'mathProfile'])),
            'progress' => $user->levelProgress()->get(),
            'attempts' => $user->levelAttempts()->get(),
            'xp_ledger' => $user->xpLedgerEntries()->get(),
            'documents' => $user->exerciseDocuments()->get(),
        ];

        return response()->streamDownload(function () use ($payload) {
            echo json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }, "mathdashboard-user-{$user->id}.json", ['Content-Type' => 'application/json']);
    }

    private function listPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'avatar' => $user->avatar_path,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'country' => $user->country,
            'locale' => $user->locale,
            'xp' => (int) ($user->mathProfile?->total_xp ?? 0),
            'level' => $user->mathProfile?->current_rank,
            'streak' => (int) ($user->mathProfile?->streak_days ?? 0),
            'last_active_at' => $user->last_active_at,
            'created_at' => $user->created_at,
            'level_attempts_count' => $user->level_attempts_count ?? null,
            'exercise_documents_count' => $user->exercise_documents_count ?? null,
            'generation_logs_count' => $user->generation_logs_count ?? null,
        ];
    }

    private function applyStatus(
        UserAdministrationService $service,
        User $user,
        string $status,
        string $reason,
        string $message,
    ): string {
        $service->setStatus($user, $status, $reason);

        return $message;
    }

    private function applyEmailVerification(
        UserAdministrationService $service,
        User $user,
        string $reason,
    ): string {
        $service->verifyEmail($user, $reason);

        return 'Email verified.';
    }

    private function changeRole(
        UserAdminActionRequest $request,
        User $user,
        UserAdministrationService $service,
        string $reason,
    ): string {
        Gate::authorize('manageRoles', User::class);
        if ($request->user()->is($user) && $request->validated('role') !== User::ROLE_ADMIN) {
            throw ValidationException::withMessages(['role' => 'You cannot remove your own administrator role.']);
        }
        $service->changeRole($user, $request->validated('role'), $reason);

        return 'User role updated.';
    }

    private function sendPasswordReset(User $user, AuditService $audit): string
    {
        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status !== Password::RESET_LINK_SENT) {
            throw ValidationException::withMessages(['email' => __($status)]);
        }
        $audit->record('user.password_reset_requested', $user, [], [], 'Safe reset link sent.');

        return 'Password reset link sent.';
    }
}
