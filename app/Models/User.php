<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ROLE_STUDENT = 'student';
    public const ROLE_LECTURER = 'lecturer';
    public const ROLE_ADMIN = 'admin';

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'role',
        'status',
        'locale',
        'country_id',
        'avatar_path',
        'last_login_at',
        'last_active_at',
        'suspended_at',
        'suspended_reason',
        'must_enable_2fa',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => 'string',
            'must_enable_2fa' => 'boolean',
            'last_login_at' => 'datetime',
            'last_active_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function mathProfile(): HasOne
    {
        return $this->hasOne(MathUserProfile::class);
    }

    public function levelProgress(): HasMany
    {
        return $this->hasMany(MathUserLevelProgress::class);
    }

    public function levelAttempts(): HasMany
    {
        return $this->hasMany(MathLevelAttempt::class);
    }

    public function xpEvents(): HasMany
    {
        return $this->hasMany(MathUserXpEvent::class);
    }

    public function xpLedgerEntries(): HasMany
    {
        return $this->hasMany(XpLedgerEntry::class);
    }

    public function exerciseDocuments(): HasMany
    {
        return $this->hasMany(ExerciseDocument::class);
    }

    public function generationLogs(): HasMany
    {
        return $this->hasMany(ExerciseGenerationLog::class);
    }

    public function lecturerProfile(): HasOne
    {
        return $this->hasOne(LecturerProfile::class);
    }

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(Permission::class, 'user_permissions')
            ->withPivot(['allowed', 'granted_by'])
            ->withTimestamps();
    }

    public function hasCmsPermission(string $permission): bool
    {
        $roleAllowed = Permission::query()
            ->where('key', $permission)
            ->whereHas('roles', fn ($query) => $query->where('role', $this->role))
            ->exists();

        $override = $this->permissions()
            ->where('permissions.key', $permission)
            ->first();

        return $override ? (bool) $override->pivot->allowed : $roleAllowed;
    }

    public function cmsPermissionKeys(): array
    {
        $roleKeys = Permission::query()
            ->whereHas('roles', fn ($query) => $query->where('role', $this->role))
            ->pluck('key')
            ->all();

        $keys = array_fill_keys($roleKeys, true);
        foreach ($this->permissions()->get() as $permission) {
            $keys[$permission->key] = (bool) $permission->pivot->allowed;
        }

        return array_keys(array_filter($keys));
    }

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isLecturer(): bool
    {
        return $this->role === self::ROLE_LECTURER;
    }

    public function canUseEditors(): bool
    {
        return $this->isAdmin() || $this->isLecturer();
    }

    public function canManageFormulas(): bool
    {
        return $this->isAdmin();
    }
}
