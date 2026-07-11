<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CmsOverviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_fetch_real_ai_usage_summary_in_overview(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $author = User::factory()->create();

        $documentId = DB::table('exercise_documents')->insertGetId([
            'user_id' => $author->id,
            'title' => 'AI Worksheet',
            'status' => 'draft',
            'language' => 'en',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('exercise_generation_logs')->insert([
            [
                'user_id' => $author->id,
                'document_id' => $documentId,
                'status' => 'completed',
                'prompt' => 'Generate algebra worksheet',
                'error' => null,
                'started_at' => now()->subMinutes(10),
                'finished_at' => now()->subMinutes(9),
                'created_at' => now()->subMinutes(10),
                'updated_at' => now()->subMinutes(9),
            ],
            [
                'user_id' => $author->id,
                'document_id' => $documentId,
                'status' => 'failed',
                'prompt' => 'Generate geometry worksheet',
                'error' => 'Provider timeout',
                'started_at' => now()->subMinutes(8),
                'finished_at' => now()->subMinutes(7),
                'created_at' => now()->subMinutes(8),
                'updated_at' => now()->subMinutes(7),
            ],
            [
                'user_id' => $author->id,
                'document_id' => null,
                'status' => 'processing',
                'prompt' => 'Generate probability worksheet',
                'error' => null,
                'started_at' => now()->subMinutes(5),
                'finished_at' => null,
                'created_at' => now()->subMinutes(5),
                'updated_at' => now()->subMinutes(5),
            ],
            [
                'user_id' => $author->id,
                'document_id' => null,
                'status' => 'completed',
                'prompt' => 'Old log outside current month',
                'error' => null,
                'started_at' => now()->subMonth()->startOfMonth(),
                'finished_at' => now()->subMonth()->startOfMonth()->addMinute(),
                'created_at' => now()->subMonth()->startOfMonth(),
                'updated_at' => now()->subMonth()->startOfMonth()->addMinute(),
            ],
        ]);

        $response = $this->actingAs($admin)->getJson('/cms-api/overview');

        $response
            ->assertOk()
            ->assertJsonPath('ai_usage.generations', 3)
            ->assertJsonPath('ai_usage.successful', 1)
            ->assertJsonPath('ai_usage.failed', 1)
            ->assertJsonPath('ai_usage.processing', 1)
            ->assertJsonPath('ai_usage.documents', 1)
            ->assertJsonPath('ai_usage.average_duration_seconds', 60)
            ->assertJsonPath('ai_usage.percent', 33);
    }
}
