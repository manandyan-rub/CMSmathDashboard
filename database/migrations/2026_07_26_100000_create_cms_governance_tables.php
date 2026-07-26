<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role', 'users_role_index');
            $table->string('status', 20)->default('active')->after('role')->index();
            $table->string('locale', 10)->default('en')->after('status')->index();
            $table->string('avatar_path')->nullable()->after('locale');
            $table->timestamp('last_login_at')->nullable()->after('avatar_path')->index();
            $table->timestamp('last_active_at')->nullable()->after('last_login_at')->index();
            $table->timestamp('suspended_at')->nullable()->after('last_active_at');
            $table->text('suspended_reason')->nullable()->after('suspended_at');
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->string('group', 60)->index();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->string('role', 30)->index();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['role', 'permission_id']);
        });

        Schema::create('user_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained()->cascadeOnDelete();
            $table->boolean('allowed')->default(true);
            $table->foreignId('granted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'permission_id']);
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('administrator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 120)->index();
            $table->string('entity_type', 160)->nullable()->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->uuid('request_id')->index();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id', 'created_at']);
            $table->index(['administrator_id', 'created_at']);
        });

        Schema::create('content_versions', function (Blueprint $table) {
            $table->id();
            $table->string('versionable_type', 160);
            $table->unsignedBigInteger('versionable_id');
            $table->unsignedInteger('version_number');
            $table->json('changed_fields')->nullable();
            $table->json('previous_data')->nullable();
            $table->json('new_data')->nullable();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reason')->nullable();
            $table->timestamps();
            $table->unique(
                ['versionable_type', 'versionable_id', 'version_number'],
                'content_versions_entity_version_unique'
            );
            $table->index(
                ['versionable_type', 'versionable_id', 'created_at'],
                'content_versions_entity_created_index'
            );
        });

        Schema::create('content_workflows', function (Blueprint $table) {
            $table->id();
            $table->string('workflowable_type', 160);
            $table->unsignedBigInteger('workflowable_id');
            $table->string('state', 20)->default('draft')->index();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('reviewer_comment')->nullable();
            $table->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('published_at')->nullable()->index();
            $table->timestamp('scheduled_at')->nullable()->index();
            $table->timestamps();
            $table->unique(
                ['workflowable_type', 'workflowable_id'],
                'content_workflows_entity_unique'
            );
        });

        Schema::create('progress_resets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('level_id')->nullable()->constrained('math_levels')->nullOnDelete();
            $table->foreignId('administrator_id')->constrained('users')->restrictOnDelete();
            $table->text('reason');
            $table->json('snapshot')->nullable();
            $table->timestamp('reset_at')->useCurrent();
            $table->timestamps();
            $table->index(['user_id', 'reset_at']);
        });

        Schema::create('xp_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->integer('amount');
            $table->unsignedInteger('balance_after');
            $table->string('source', 60)->default('admin_adjustment')->index();
            $table->text('reason');
            $table->foreignId('administrator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_type', 160)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['user_id', 'occurred_at']);
        });

        Schema::create('lecturer_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('approval_status', 20)->default('pending')->index();
            $table->unsignedInteger('monthly_ai_limit')->default(100);
            $table->unsignedBigInteger('storage_limit_bytes')->default(104857600);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('suspended_at')->nullable();
            $table->text('moderation_note')->nullable();
            $table->timestamps();
        });

        Schema::create('content_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('event', 60)->index();
            $table->string('entity_type', 160)->index();
            $table->unsignedBigInteger('entity_id')->nullable()->index();
            $table->string('locale', 10)->nullable()->index();
            $table->json('metadata')->nullable();
            $table->timestamp('occurred_at')->useCurrent()->index();
            $table->timestamps();
            $table->index(['entity_type', 'entity_id', 'occurred_at']);
        });

        $now = now();
        $permissions = collect(config('cms.permissions', []))
            ->map(fn (string $label, string $key) => [
                'key' => $key,
                'label' => $label,
                'group' => str($key)->before('.')->toString(),
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->values()
            ->all();

        DB::table('permissions')->insert($permissions);

        $permissionIds = DB::table('permissions')->pluck('id', 'key');
        $roleRows = [];
        foreach (config('cms.role_permissions', []) as $role => $keys) {
            $resolvedKeys = in_array('*', $keys, true) ? $permissionIds->keys() : collect($keys);
            foreach ($resolvedKeys as $key) {
                if (!$permissionIds->has($key)) {
                    continue;
                }
                $roleRows[] = [
                    'role' => $role,
                    'permission_id' => $permissionIds[$key],
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }
        DB::table('role_permissions')->insert($roleRows);
    }

    public function down(): void
    {
        Schema::dropIfExists('content_events');
        Schema::dropIfExists('lecturer_profiles');
        Schema::dropIfExists('xp_ledger_entries');
        Schema::dropIfExists('progress_resets');
        Schema::dropIfExists('content_workflows');
        Schema::dropIfExists('content_versions');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('user_permissions');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');

        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex('users_role_index');
            $table->dropColumn([
                'status',
                'locale',
                'avatar_path',
                'last_login_at',
                'last_active_at',
                'suspended_at',
                'suspended_reason',
            ]);
        });
    }
};
