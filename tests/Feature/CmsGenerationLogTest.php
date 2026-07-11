<?php

namespace Tests\Feature;

use App\Models\ExerciseDocument;
use App\Models\ExerciseGenerationLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsGenerationLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_list_generation_logs_with_filters_and_without_large_payload_columns(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $targetUser = User::factory()->create([
            'name' => 'Target Author',
            'email' => 'target@example.com',
        ]);
        $otherUser = User::factory()->create([
            'name' => 'Other Author',
            'email' => 'other@example.com',
        ]);

        $document = ExerciseDocument::create([
            'user_id' => $targetUser->id,
            'title' => 'Worksheet A',
            'status' => 'draft',
            'language' => 'en',
        ]);

        $matchingLog = ExerciseGenerationLog::create([
            'user_id' => $targetUser->id,
            'document_id' => $document->id,
            'status' => 'failed',
            'prompt' => 'Generate a worksheet',
            'request_json' => '{"model":"gpt"}',
            'response_raw' => '{"error":"timeout"}',
            'parsed_json' => '{"items":[]}',
            'error' => str_repeat('Timeout from provider. ', 12),
            'started_at' => now()->subDay()->setTime(10, 0),
            'finished_at' => now()->subDay()->setTime(10, 1),
        ]);

        ExerciseGenerationLog::create([
            'user_id' => $targetUser->id,
            'document_id' => null,
            'status' => 'completed',
            'prompt' => 'Generate another worksheet',
            'request_json' => '{"ignored":true}',
            'response_raw' => '{"ok":true}',
            'parsed_json' => '{"items":[1]}',
            'error' => null,
            'started_at' => now()->subDays(5),
            'finished_at' => now()->subDays(5)->addMinute(),
        ]);

        ExerciseGenerationLog::create([
            'user_id' => $otherUser->id,
            'document_id' => null,
            'status' => 'failed',
            'prompt' => 'Generate for another user',
            'request_json' => '{"ignored":true}',
            'response_raw' => '{"ok":false}',
            'parsed_json' => null,
            'error' => 'Other failure',
            'started_at' => now()->subDay()->setTime(11, 0),
            'finished_at' => now()->subDay()->setTime(11, 1),
        ]);

        $response = $this->actingAs($admin)->getJson('/cms-api/generation-logs?status=failed&user_id='.$targetUser->id.'&date_from='.now()->subDays(2)->toDateString().'&date_to='.now()->toDateString());

        $response
            ->assertOk()
            ->assertJsonPath('items.0.id', $matchingLog->id)
            ->assertJsonPath('items.0.status', 'failed')
            ->assertJsonPath('items.0.user.id', $targetUser->id)
            ->assertJsonPath('items.0.user.name', 'Target Author')
            ->assertJsonPath('items.0.document.id', $document->id)
            ->assertJsonPath('items.0.document.title', 'Worksheet A')
            ->assertJsonPath('items.0.duration_seconds', 60)
            ->assertJsonCount(1, 'items');

        $this->assertArrayNotHasKey('request_json', $response->json('items.0'));
        $this->assertArrayNotHasKey('response_raw', $response->json('items.0'));
        $this->assertArrayNotHasKey('parsed_json', $response->json('items.0'));
        $this->assertNotNull($response->json('items.0.error_excerpt'));
        $this->assertLessThanOrEqual(140, strlen((string) $response->json('items.0.error_excerpt')));
    }

    public function test_admin_can_fetch_single_generation_log_detail_with_full_payload_columns(): void
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        $author = User::factory()->create([
            'name' => 'Prompt Author',
            'email' => 'author@example.com',
        ]);

        $document = ExerciseDocument::create([
            'user_id' => $author->id,
            'title' => 'Preview Worksheet',
            'status' => 'published',
            'language' => 'fr',
        ]);

        $log = ExerciseGenerationLog::create([
            'user_id' => $author->id,
            'document_id' => $document->id,
            'status' => 'completed',
            'prompt' => 'Generate 5 algebra questions',
            'request_json' => '{"model":"gpt-5","temperature":0.2}',
            'response_raw' => '{"output":"raw text"}',
            'parsed_json' => '{"items":[{"question":"Q1"}]}',
            'error' => null,
            'started_at' => now()->subMinutes(3),
            'finished_at' => now()->subMinutes(2),
        ]);

        $response = $this->actingAs($admin)->getJson("/cms-api/generation-logs/{$log->id}");

        $response
            ->assertOk()
            ->assertJsonPath('item.id', $log->id)
            ->assertJsonPath('item.status', 'completed')
            ->assertJsonPath('item.user.email', 'author@example.com')
            ->assertJsonPath('item.document.title', 'Preview Worksheet')
            ->assertJsonPath('item.prompt', 'Generate 5 algebra questions')
            ->assertJsonPath('item.request_json', '{"model":"gpt-5","temperature":0.2}')
            ->assertJsonPath('item.response_raw', '{"output":"raw text"}')
            ->assertJsonPath('item.parsed_json', '{"items":[{"question":"Q1"}]}')
            ->assertJsonPath('item.duration_seconds', 60);
    }
}
