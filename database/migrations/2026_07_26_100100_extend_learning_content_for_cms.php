<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('math_paths', function (Blueprint $table) {
            $table->string('icon')->nullable()->after('slug');
            $table->string('cover_image')->nullable()->after('icon');
            $table->string('difficulty_min', 30)->nullable()->after('cover_image');
            $table->string('difficulty_max', 30)->nullable()->after('difficulty_min');
            $table->unsignedInteger('minimum_required_level')->default(0)->after('difficulty_max');
            $table->timestamp('archived_at')->nullable()->after('active')->index();
            $table->softDeletes();
        });

        Schema::create('math_path_prerequisites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('path_id')->constrained('math_paths')->cascadeOnDelete();
            $table->foreignId('prerequisite_path_id')->constrained('math_paths')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['path_id', 'prerequisite_path_id'], 'path_prerequisites_unique');
        });

        Schema::table('math_levels', function (Blueprint $table) {
            $table->unsignedInteger('allowed_attempts')->nullable()->after('time_limit');
            $table->boolean('hints_enabled')->default(true)->after('allowed_attempts');
            $table->boolean('calculator_enabled')->default(true)->after('hints_enabled');
            $table->timestamp('archived_at')->nullable()->after('active')->index();
            $table->softDeletes();
        });

        Schema::table('math_level_attempts', function (Blueprint $table) {
            $table->unsignedInteger('hints_used')->default(0)->after('wrong_answers');
            $table->unsignedInteger('duration_seconds')->nullable()->after('hints_used');
            $table->index(['level_id', 'finished_at']);
            $table->index(['user_id', 'finished_at']);
        });

        Schema::table('math_level_attempt_questions', function (Blueprint $table) {
            $table->foreignId('source_question_id')
                ->nullable()
                ->after('attempt_id')
                ->constrained('math_level_questions')
                ->nullOnDelete();
        });

        Schema::table('formulas', function (Blueprint $table) {
            $table->foreignId('group_id')->nullable()->after('id')->constrained('formula_groups')->nullOnDelete();
            $table->json('variables')->nullable()->after('example');
            $table->json('units')->nullable()->after('variables');
            $table->text('usage_notes')->nullable()->after('units');
            $table->string('visual_proof_reference')->nullable()->after('usage_notes');
            $table->unsignedInteger('sort_order')->default(1000)->after('visual_proof_reference');
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();
            $table->index(['category', 'sort_order']);
        });

        Schema::create('formula_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('formula_id')->constrained()->cascadeOnDelete();
            $table->string('related_type', 40);
            $table->string('related_key', 120);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->unique(['formula_id', 'related_type', 'related_key']);
        });

        Schema::table('math_level_questions', function (Blueprint $table) {
            $table->string('topic')->nullable()->after('level_id')->index();
            $table->string('difficulty', 30)->nullable()->after('topic')->index();
            $table->json('answer_config')->nullable()->after('correct_answer');
            $table->decimal('tolerance', 12, 6)->nullable()->after('answer_config');
            $table->string('accepted_unit', 40)->nullable()->after('tolerance');
            $table->json('hints')->nullable()->after('accepted_unit');
            $table->string('image_path')->nullable()->after('hints');
            $table->foreignId('formula_id')->nullable()->after('image_path')->constrained()->nullOnDelete();
            $table->string('calculator_key', 80)->nullable()->after('formula_id')->index();
            $table->unsignedInteger('xp_value')->default(1)->after('points');
            $table->timestamp('archived_at')->nullable()->after('active')->index();
            $table->softDeletes();
            $table->index(['level_id', 'active', 'order']);
        });

        Schema::create('math_level_question_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained('math_level_questions')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->longText('question');
            $table->text('explanation')->nullable();
            $table->json('hints')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->timestamp('source_updated_at')->nullable();
            $table->timestamps();
            $table->unique(['question_id', 'locale']);
        });

        Schema::table('math_level_question_options', function (Blueprint $table) {
            $table->text('explanation')->nullable()->after('option_text');
        });

        Schema::create('math_level_question_option_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('option_id')->constrained('math_level_question_options')->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('label');
            $table->text('explanation')->nullable();
            $table->boolean('reviewed')->default(false);
            $table->timestamps();
            $table->unique(['option_id', 'locale']);
        });

        Schema::create('cms_translations', function (Blueprint $table) {
            $table->id();
            $table->string('translatable_type', 160);
            $table->unsignedBigInteger('translatable_id');
            $table->string('locale', 10)->index();
            $table->json('values');
            $table->boolean('reviewed')->default(false)->index();
            $table->boolean('machine_draft')->default(false);
            $table->timestamp('source_updated_at')->nullable();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->unique(
                ['translatable_type', 'translatable_id', 'locale'],
                'cms_translations_entity_locale_unique'
            );
        });

        Schema::create('calculator_contents', function (Blueprint $table) {
            $table->id();
            $table->string('key', 80)->unique();
            $table->string('category', 80)->index();
            $table->string('icon')->nullable();
            $table->boolean('active')->default(true)->index();
            $table->unsignedInteger('display_order')->default(1000);
            $table->json('supported_units')->nullable();
            $table->string('default_unit', 40)->nullable();
            $table->unsignedTinyInteger('default_precision')->default(2);
            $table->json('metadata')->nullable();
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });

        Schema::create('calculator_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculator_content_id')->constrained()->cascadeOnDelete();
            $table->string('key', 80);
            $table->string('input_type', 30)->default('number');
            $table->json('example_values')->nullable();
            $table->json('supported_units')->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->unique(['calculator_content_id', 'key']);
        });

        Schema::create('calculator_relations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('calculator_content_id')->constrained()->cascadeOnDelete();
            $table->string('related_type', 40);
            $table->string('related_key', 120);
            $table->timestamps();
            $table->unique(
                ['calculator_content_id', 'related_type', 'related_key'],
                'calculator_relations_unique'
            );
        });

        Schema::create('practice_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key', 120)->unique();
            $table->string('topic', 100)->index();
            $table->foreignId('calculator_content_id')->nullable()->constrained()->nullOnDelete();
            $table->string('difficulty', 30)->default('medium')->index();
            $table->json('entered_fields');
            $table->json('requested_fields');
            $table->json('value_ranges')->nullable();
            $table->boolean('integer_only')->default(false);
            $table->boolean('exact_value')->default(false);
            $table->unsignedTinyInteger('decimal_precision')->default(2);
            $table->json('allowed_units')->nullable();
            $table->unsignedInteger('xp_reward')->default(5);
            $table->boolean('active')->default(true)->index();
            $table->timestamp('archived_at')->nullable()->index();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('practice_templates');
        Schema::dropIfExists('calculator_relations');
        Schema::dropIfExists('calculator_fields');
        Schema::dropIfExists('calculator_contents');
        Schema::dropIfExists('cms_translations');
        Schema::dropIfExists('math_level_question_option_translations');
        Schema::table('math_level_question_options', fn (Blueprint $table) => $table->dropColumn('explanation'));
        Schema::dropIfExists('math_level_question_translations');

        Schema::table('math_level_attempt_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('source_question_id');
        });
        Schema::table('math_level_attempts', function (Blueprint $table) {
            $table->dropColumn(['hints_used', 'duration_seconds']);
        });

        Schema::table('math_level_questions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('formula_id');
            $table->dropColumn([
                'topic',
                'difficulty',
                'answer_config',
                'tolerance',
                'accepted_unit',
                'hints',
                'image_path',
                'calculator_key',
                'xp_value',
                'archived_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('formula_relations');
        Schema::table('formulas', function (Blueprint $table) {
            $table->dropConstrainedForeignId('group_id');
            $table->dropColumn([
                'variables',
                'units',
                'usage_notes',
                'visual_proof_reference',
                'sort_order',
                'archived_at',
                'deleted_at',
            ]);
        });

        Schema::table('math_levels', function (Blueprint $table) {
            $table->dropColumn([
                'allowed_attempts',
                'hints_enabled',
                'calculator_enabled',
                'archived_at',
                'deleted_at',
            ]);
        });

        Schema::dropIfExists('math_path_prerequisites');
        Schema::table('math_paths', function (Blueprint $table) {
            $table->dropColumn([
                'icon',
                'cover_image',
                'difficulty_min',
                'difficulty_max',
                'minimum_required_level',
                'archived_at',
                'deleted_at',
            ]);
        });
    }
};
