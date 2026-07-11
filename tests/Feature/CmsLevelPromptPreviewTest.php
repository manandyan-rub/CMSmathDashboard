<?php

namespace Tests\Feature;

use App\Models\ExerciseGenerationLog;
use App\Models\MathLevel;
use App\Models\MathLevelPrompt;
use App\Models\MathLevelTranslation;
use App\Models\MathPath;
use App\Models\MathPathTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsLevelPromptPreviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_preview_level_prompt_with_mock_notice_and_tagged_log(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $path = MathPath::create([
            'slug' => 'algebra-path',
            'order' => 1,
            'active' => true,
        ]);

        MathPathTranslation::create([
            'math_path_id' => $path->id,
            'locale' => 'en',
            'title' => 'Algebra Path',
            'description' => 'Path description',
        ]);

        $level = MathLevel::create([
            'path_id' => $path->id,
            'slug' => 'linear-equations',
            'difficulty' => 'medium',
            'order' => 1,
            'passing_score' => 70,
            'xp_reward' => 50,
            'time_limit' => 120,
            'active' => true,
        ]);

        MathLevelTranslation::create([
            'math_level_id' => $level->id,
            'locale' => 'en',
            'title' => 'Linear Equations',
            'topic' => 'Equations',
            'description' => 'Level description',
        ]);

        MathLevelPrompt::create([
            'level_id' => $level->id,
            'prompt' => 'Original stored prompt',
            'question_count' => 8,
            'options_count' => 4,
            'locale' => 'en',
        ]);

        $response = $this->actingAs($admin)->postJson("/cms-api/levels/{$level->id}/prompt-preview", [
            'prompt' => 'Draft preview prompt',
            'question_count' => 5,
            'options_count' => 4,
            'locale' => 'en',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('item.notice', '[PREVIEW - MOCK RESPONSE, not real AI output]')
            ->assertJsonPath('item.source', 'preview')
            ->assertJsonPath('item.status', 'completed')
            ->assertJsonPath('item.preview.generator', 'cms-mock-preview')
            ->assertJsonPath('item.preview.items.0.question', '[PREVIEW - MOCK RESPONSE, not real AI output] Sample question 1 for Linear Equations in Algebra Path.')
            ->assertJsonPath('item.logged_generation.source', 'preview');

        $log = ExerciseGenerationLog::query()->sole();

        $this->assertSame('preview', $log->source);
        $this->assertStringStartsWith('[PREVIEW - MOCK RESPONSE, not real AI output]', $log->prompt);
        $this->assertStringStartsWith('[PREVIEW - MOCK RESPONSE, not real AI output]', (string) $log->response_raw);
        $this->assertStringContainsString('Draft preview prompt', (string) $log->request_json);
    }

    public function test_preview_endpoint_is_rate_limited(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $path = MathPath::create([
            'slug' => 'geometry-path',
            'order' => 1,
            'active' => true,
        ]);

        $level = MathLevel::create([
            'path_id' => $path->id,
            'slug' => 'triangles',
            'difficulty' => 'easy',
            'order' => 1,
            'passing_score' => 70,
            'xp_reward' => 25,
            'time_limit' => 90,
            'active' => true,
        ]);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->actingAs($admin)->postJson("/cms-api/levels/{$level->id}/prompt-preview", [
                'prompt' => 'Rate limit probe',
                'question_count' => 5,
                'options_count' => 4,
                'locale' => 'en',
            ])->assertOk();
        }

        $this->actingAs($admin)->postJson("/cms-api/levels/{$level->id}/prompt-preview", [
            'prompt' => 'Rate limit probe',
            'question_count' => 5,
            'options_count' => 4,
            'locale' => 'en',
        ])->assertStatus(429);
    }
}
