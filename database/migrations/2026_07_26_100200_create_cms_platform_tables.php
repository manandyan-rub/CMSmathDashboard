<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_providers', function (Blueprint $table) {
            $table->id();
            $table->string('key', 60)->unique();
            $table->string('name');
            $table->string('driver', 40)->default('openai_compatible');
            $table->string('base_url')->nullable();
            $table->string('secret_env_key')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_models', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_id')->constrained('ai_providers')->cascadeOnDelete();
            $table->string('key', 100);
            $table->string('name');
            $table->unsignedInteger('max_output_tokens')->default(2048);
            $table->decimal('default_temperature', 4, 2)->default(0.20);
            $table->unsignedInteger('timeout_seconds')->default(60);
            $table->unsignedTinyInteger('retry_count')->default(1);
            $table->decimal('input_cost_per_million', 12, 6)->nullable();
            $table->decimal('output_cost_per_million', 12, 6)->nullable();
            $table->boolean('active')->default(false)->index();
            $table->timestamps();
            $table->unique(['provider_id', 'key']);
        });

        Schema::create('ai_prompts', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('category', 80)->index();
            $table->string('feature', 80)->index();
            $table->foreignId('model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->foreignId('fallback_model_id')->nullable()->constrained('ai_models')->nullOnDelete();
            $table->unsignedInteger('monthly_limit')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->timestamps();
        });

        Schema::create('ai_prompt_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('prompt_id')->constrained('ai_prompts')->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->longText('system_prompt');
            $table->longText('user_prompt_template');
            $table->json('input_schema')->nullable();
            $table->json('output_schema')->nullable();
            $table->decimal('temperature', 4, 2)->nullable();
            $table->unsignedInteger('max_output_tokens')->nullable();
            $table->boolean('active')->default(false)->index();
            $table->foreignId('author_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('change_reason')->nullable();
            $table->timestamps();
            $table->unique(['prompt_id', 'version']);
        });

        Schema::create('platform_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('group', 60)->default('general')->index();
            $table->string('type', 30)->default('string');
            $table->json('value')->nullable();
            $table->json('default_value')->nullable();
            $table->json('validation_rules')->nullable();
            $table->string('label');
            $table->text('description')->nullable();
            $table->boolean('public')->default(false)->index();
            $table->boolean('editable')->default(true);
            $table->timestamps();
        });

        Schema::create('feature_flags', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('title');
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(false)->index();
            $table->string('environment', 30)->default('all')->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('public')->default(true);
            $table->timestamps();
        });

        Schema::create('xp_rules', function (Blueprint $table) {
            $table->id();
            $table->string('action_type', 100)->index();
            $table->string('topic', 100)->nullable()->index();
            $table->string('difficulty', 30)->nullable()->index();
            $table->integer('base_xp');
            $table->integer('bonus_xp')->default(0);
            $table->unsignedInteger('daily_cap')->nullable();
            $table->unsignedInteger('maximum_rewarded_attempts')->nullable();
            $table->boolean('repeated_answer_protection')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('achievements', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('icon')->nullable();
            $table->string('badge_color', 30)->default('indigo');
            $table->json('criteria');
            $table->unsignedInteger('xp_reward')->default(0);
            $table->boolean('active')->default(true)->index();
            $table->timestamps();
        });

        Schema::create('user_achievements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('achievement_id')->constrained()->cascadeOnDelete();
            $table->timestamp('awarded_at')->useCurrent();
            $table->foreignId('awarded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->unique(['user_id', 'achievement_id']);
        });

        Schema::create('rewards', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('type', 40)->index();
            $table->unsignedInteger('cost_xp')->default(0);
            $table->json('payload')->nullable();
            $table->unsignedInteger('stock')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });

        Schema::create('user_rewards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('reward_id')->constrained()->cascadeOnDelete();
            $table->string('status', 20)->default('earned')->index();
            $table->timestamp('earned_at')->useCurrent();
            $table->timestamp('redeemed_at')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('leaderboard_seasons', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('title');
            $table->timestamp('starts_at')->index();
            $table->timestamp('ends_at')->nullable()->index();
            $table->boolean('active')->default(false)->index();
            $table->json('excluded_user_ids')->nullable();
            $table->json('rules')->nullable();
            $table->timestamps();
        });

        Schema::create('game_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->string('icon')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->string('difficulty', 30)->default('medium')->index();
            $table->json('number_range')->nullable();
            $table->unsignedInteger('question_count')->default(10);
            $table->unsignedInteger('time_limit')->nullable();
            $table->unsignedInteger('xp_reward')->default(5);
            $table->unsignedInteger('daily_limit')->nullable();
            $table->boolean('leaderboard_included')->default(true);
            $table->timestamps();
        });

        Schema::create('game_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('game_definition_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('score')->default(0);
            $table->boolean('completed')->default(false)->index();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->json('mistakes')->nullable();
            $table->timestamp('started_at')->nullable()->index();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
            $table->index(['game_definition_id', 'started_at']);
            $table->index(['user_id', 'started_at']);
        });

        Schema::create('streak_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('streak_days');
            $table->string('event', 30);
            $table->date('streak_date')->index();
            $table->timestamps();
            $table->unique(['user_id', 'streak_date', 'event']);
        });

        Schema::create('moderation_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('document_id')->constrained('exercise_documents')->cascadeOnDelete();
            $table->foreignId('administrator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status', 30)->default('note')->index();
            $table->text('note');
            $table->timestamps();
            $table->index(['document_id', 'created_at']);
        });

        Schema::create('export_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requested_by')->constrained('users')->cascadeOnDelete();
            $table->string('resource', 80)->index();
            $table->string('format', 20)->default('csv');
            $table->json('filters')->nullable();
            $table->string('status', 20)->default('pending')->index();
            $table->string('file_path')->nullable();
            $table->text('error')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::table('exercise_documents', function (Blueprint $table) {
            $table->string('creation_source', 30)->default('manual')->after('status')->index();
            $table->boolean('ai_generated')->default(false)->after('creation_source')->index();
            $table->unsignedInteger('page_count')->default(1)->after('language');
            $table->string('publication_status', 20)->default('draft')->after('page_count')->index();
            $table->string('moderation_status', 20)->default('unreviewed')->after('publication_status')->index();
            $table->foreignId('moderated_by')->nullable()->after('moderation_status')->constrained('users')->nullOnDelete();
            $table->timestamp('moderated_at')->nullable()->after('moderated_by');
            $table->timestamp('featured_at')->nullable()->after('published_at');
            $table->timestamp('archived_at')->nullable()->after('featured_at')->index();
            $table->softDeletes();
        });

        Schema::table('exercise_items', function (Blueprint $table) {
            $table->boolean('edited_after_ai')->default(false)->after('source');
            $table->json('source_metadata')->nullable()->after('edited_after_ai');
        });

        Schema::table('exercise_generation_logs', function (Blueprint $table) {
            $table->foreignId('provider_id')->nullable()->after('source')->constrained('ai_providers')->nullOnDelete();
            $table->foreignId('model_id')->nullable()->after('provider_id')->constrained('ai_models')->nullOnDelete();
            $table->foreignId('prompt_version_id')->nullable()->after('model_id')->constrained('ai_prompt_versions')->nullOnDelete();
            $table->string('feature', 80)->nullable()->after('prompt_version_id')->index();
            $table->unsignedInteger('input_tokens')->nullable()->after('error');
            $table->unsignedInteger('output_tokens')->nullable()->after('input_tokens');
            $table->decimal('estimated_cost', 12, 6)->nullable()->after('output_tokens');
            $table->unsignedInteger('latency_ms')->nullable()->after('estimated_cost');
            $table->unsignedTinyInteger('retry_count')->default(0)->after('latency_ms');
            $table->index(['model_id', 'created_at']);
            $table->index(['feature', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::table('exercise_generation_logs', function (Blueprint $table) {
            $table->dropConstrainedForeignId('prompt_version_id');
            $table->dropConstrainedForeignId('model_id');
            $table->dropConstrainedForeignId('provider_id');
            $table->dropColumn([
                'feature',
                'input_tokens',
                'output_tokens',
                'estimated_cost',
                'latency_ms',
                'retry_count',
            ]);
        });

        Schema::table('exercise_items', fn (Blueprint $table) => $table->dropColumn(['edited_after_ai', 'source_metadata']));
        Schema::table('exercise_documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('moderated_by');
            $table->dropColumn([
                'creation_source',
                'ai_generated',
                'page_count',
                'publication_status',
                'moderation_status',
                'moderated_at',
                'featured_at',
                'archived_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('export_requests');
        Schema::dropIfExists('moderation_notes');
        Schema::dropIfExists('streak_history');
        Schema::dropIfExists('game_sessions');
        Schema::dropIfExists('game_definitions');
        Schema::dropIfExists('leaderboard_seasons');
        Schema::dropIfExists('user_rewards');
        Schema::dropIfExists('rewards');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievements');
        Schema::dropIfExists('xp_rules');
        Schema::dropIfExists('feature_flags');
        Schema::dropIfExists('platform_settings');
        Schema::dropIfExists('ai_prompt_versions');
        Schema::dropIfExists('ai_prompts');
        Schema::dropIfExists('ai_models');
        Schema::dropIfExists('ai_providers');
    }
};
