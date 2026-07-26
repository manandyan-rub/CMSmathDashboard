<?php

namespace App\Services\Cms;

use App\Models\LecturerProfile;
use App\Models\MathUserLevelProgress;
use App\Models\MathUserProfile;
use App\Models\ProgressReset;
use App\Models\User;
use App\Models\XpLedgerEntry;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UserAdministrationService
{
    public function __construct(private readonly AuditService $audit)
    {
    }

    public function setStatus(User $user, string $status, string $reason): User
    {
        $before = $this->audit->modelSnapshot($user);
        $user->update([
            'status' => $status,
            'suspended_at' => $status === 'suspended' ? now() : null,
            'suspended_reason' => $status === 'suspended' ? $reason : null,
        ]);

        $this->audit->record(
            $status === 'suspended' ? 'user.suspended' : 'user.activated',
            $user,
            $before,
            $this->audit->modelSnapshot($user->fresh()),
            $reason
        );

        return $user->fresh();
    }

    public function changeRole(User $user, string $role, string $reason): User
    {
        $before = ['role' => $user->role];
        $user->update(['role' => $role]);

        if ($role === User::ROLE_LECTURER) {
            LecturerProfile::firstOrCreate(['user_id' => $user->id]);
        }

        $this->audit->record('user.role_changed', $user, $before, ['role' => $role], $reason);

        return $user->fresh();
    }

    public function verifyEmail(User $user, string $reason): User
    {
        $before = ['email_verified_at' => $user->email_verified_at];
        if (!$user->hasVerifiedEmail()) {
            $user->forceFill(['email_verified_at' => now()])->save();
        }
        $this->audit->record(
            'user.email_verified',
            $user,
            $before,
            ['email_verified_at' => $user->email_verified_at],
            $reason
        );

        return $user->fresh();
    }

    public function adjustXp(User $user, int $amount, string $reason): XpLedgerEntry
    {
        return DB::transaction(function () use ($user, $amount, $reason) {
            $profile = MathUserProfile::query()
                ->where('user_id', $user->id)
                ->lockForUpdate()
                ->first();

            if (!$profile) {
                $profile = MathUserProfile::create(['user_id' => $user->id]);
                $profile->refresh();
            }

            $before = (int) $profile->total_xp;
            $after = $before + $amount;
            if ($after < 0) {
                throw ValidationException::withMessages([
                    'amount' => 'This adjustment would make the XP balance negative.',
                ]);
            }

            $profile->update(['total_xp' => $after]);
            $entry = XpLedgerEntry::create([
                'user_id' => $user->id,
                'amount' => $amount,
                'balance_after' => $after,
                'source' => 'admin_adjustment',
                'reason' => $reason,
                'administrator_id' => request()->user()?->id,
                'occurred_at' => now(),
            ]);

            $this->audit->record(
                'user.xp_adjusted',
                $user,
                ['total_xp' => $before],
                ['total_xp' => $after, 'ledger_entry_id' => $entry->id],
                $reason
            );

            return $entry;
        });
    }

    public function resetProgress(User $user, ?int $levelId, string $reason): ProgressReset
    {
        return DB::transaction(function () use ($user, $levelId, $reason) {
            $query = MathUserLevelProgress::query()->where('user_id', $user->id);
            if ($levelId) {
                $query->where('level_id', $levelId);
            }

            $snapshot = $query->get()->map->attributesToArray()->values()->all();
            $query->update([
                'completed' => false,
                'best_score' => 0,
                'best_stars' => 0,
                'total_attempts' => 0,
                'last_attempted_at' => null,
                'updated_at' => now(),
            ]);

            if (!$levelId) {
                MathUserProfile::query()
                    ->where('user_id', $user->id)
                    ->update([
                        'completed_levels' => 0,
                        'total_stars' => 0,
                        'streak_days' => 0,
                        'updated_at' => now(),
                    ]);
            }

            $reset = ProgressReset::create([
                'user_id' => $user->id,
                'level_id' => $levelId,
                'administrator_id' => request()->user()->id,
                'reason' => $reason,
                'snapshot' => $snapshot,
                'reset_at' => now(),
            ]);

            $this->audit->record(
                'user.progress_reset',
                $user,
                ['progress' => $snapshot],
                ['reset_id' => $reset->id, 'level_id' => $levelId],
                $reason
            );

            return $reset;
        });
    }

    public function updateLecturer(User $user, array $data): LecturerProfile
    {
        return DB::transaction(function () use ($user, $data) {
            $profile = LecturerProfile::firstOrCreate(['user_id' => $user->id]);
            $before = $this->audit->modelSnapshot($profile);
            $profile->fill($data);

            if (($data['approval_status'] ?? null) === 'approved') {
                $profile->approved_by = request()->user()->id;
                $profile->approved_at = now();
                $profile->suspended_at = null;
            }
            if (($data['approval_status'] ?? null) === 'suspended') {
                $profile->suspended_at = now();
            }

            $profile->save();
            $this->audit->record(
                'lecturer.updated',
                $profile,
                $before,
                $this->audit->modelSnapshot($profile->fresh()),
                $data['moderation_note'] ?? null
            );

            return $profile->fresh();
        });
    }
}
