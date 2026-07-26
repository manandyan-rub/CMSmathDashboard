<?php

namespace App\Services\Cms;

use App\Models\AiPrompt;
use App\Models\AiPromptVersion;
use App\Models\ExerciseGenerationLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Throwable;

class AiPromptRunnerService
{
    public function run(AiPrompt $prompt, array $input, string $source = 'cms_test'): ExerciseGenerationLog
    {
        $prompt->loadMissing(['model.provider', 'fallbackModel.provider', 'activeVersion']);
        $version = $prompt->activeVersion;
        $model = $prompt->model;

        if (!$prompt->active || !$version || !$model?->active || !$model->provider?->active) {
            throw ValidationException::withMessages([
                'prompt' => 'The prompt, model, provider, and active version must all be enabled.',
            ]);
        }

        if ($version->input_schema) {
            Validator::make($input, $version->input_schema)->validate();
        }

        $userPrompt = $this->render($version->user_prompt_template, $input);
        $payloadBase = [
            'messages' => [
                ['role' => 'system', 'content' => $version->system_prompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
        ];
        $candidates = collect([$model, $prompt->fallbackModel])
            ->filter(fn ($candidate) => $candidate?->active && $candidate->provider?->active)
            ->unique('id')
            ->values();

        $log = ExerciseGenerationLog::create([
            'user_id' => request()->user()->id,
            'source' => $source,
            'provider_id' => $model->provider->id,
            'model_id' => $model->id,
            'prompt_version_id' => $version->id,
            'feature' => $prompt->feature,
            'status' => 'processing',
            'prompt' => $userPrompt,
            'request_json' => json_encode([
                'prompt_key' => $prompt->key,
                'input' => $input,
                'candidate_models' => $candidates->pluck('key')->all(),
                'payload' => $payloadBase,
            ], JSON_UNESCAPED_UNICODE),
            'started_at' => now(),
        ]);

        $started = hrtime(true);
        $lastException = null;
        foreach ($candidates as $candidateIndex => $candidate) {
            try {
                $provider = $candidate->provider;
                $apiKey = $provider->secret_env_key
                    ? getenv($provider->secret_env_key)
                    : false;
                if (!$apiKey || !$provider->base_url) {
                    throw ValidationException::withMessages([
                        'provider' => "Provider {$provider->name} is not fully configured.",
                    ]);
                }
                $payload = array_merge($payloadBase, [
                    'model' => $candidate->key,
                    'temperature' => (float) (
                        $version->temperature ?? $candidate->default_temperature
                    ),
                    'max_tokens' => (int) (
                        $version->max_output_tokens ?? $candidate->max_output_tokens
                    ),
                ]);
                $response = Http::withToken($apiKey)
                    ->acceptJson()
                    ->timeout($candidate->timeout_seconds)
                    ->retry($candidate->retry_count, 300)
                    ->post(rtrim($provider->base_url, '/').'/chat/completions', $payload);
                $response->throw();

                $raw = $response->body();
                $content = $response->json('choices.0.message.content');
                $parsed = $this->parseContent($content);
                $validationErrors = $this->validateOutput($parsed, $version);
                $inputTokens = (int) $response->json('usage.prompt_tokens', 0);
                $outputTokens = (int) $response->json('usage.completion_tokens', 0);
                $cost = (($inputTokens / 1_000_000) * (float) (
                    $candidate->input_cost_per_million ?? 0
                )) + (($outputTokens / 1_000_000) * (float) (
                    $candidate->output_cost_per_million ?? 0
                ));

                $log->update([
                    'provider_id' => $provider->id,
                    'model_id' => $candidate->id,
                    'status' => $validationErrors ? 'failed' : 'completed',
                    'response_raw' => $raw,
                    'parsed_json' => json_encode([
                        'output' => $parsed,
                        'validation_errors' => $validationErrors,
                    ], JSON_UNESCAPED_UNICODE),
                    'error' => $validationErrors ? 'Output validation failed.' : null,
                    'input_tokens' => $inputTokens,
                    'output_tokens' => $outputTokens,
                    'estimated_cost' => $cost,
                    'latency_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
                    'retry_count' => $candidateIndex,
                    'finished_at' => now(),
                ]);

                return $log->fresh(['provider', 'model', 'promptVersion']);
            } catch (Throwable $exception) {
                $lastException = $exception;
            }
        }

        $log->update([
            'status' => 'failed',
            'error' => $lastException?->getMessage() ?? 'No active AI model was available.',
            'retry_count' => max(0, $candidates->count() - 1),
            'latency_ms' => (int) round((hrtime(true) - $started) / 1_000_000),
            'finished_at' => now(),
        ]);

        return $log->fresh(['provider', 'model', 'promptVersion']);
    }

    private function render(string $template, array $input): string
    {
        $rendered = $template;
        foreach (Arr::dot($input) as $key => $value) {
            $replacement = is_scalar($value)
                ? (string) $value
                : json_encode($value, JSON_UNESCAPED_UNICODE);
            $rendered = str_replace('{{'.$key.'}}', $replacement, $rendered);
        }

        return $rendered;
    }

    private function parseContent(mixed $content): mixed
    {
        if (!is_string($content)) {
            return $content;
        }

        $clean = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($content));
        $decoded = json_decode($clean, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $content;
    }

    private function validateOutput(mixed $output, AiPromptVersion $version): array
    {
        if (!$version->output_schema || !is_array($output)) {
            return [];
        }

        return Validator::make($output, $version->output_schema)->errors()->toArray();
    }
}
