<?php

namespace App\Services\Cms;

use App\Models\MathLevelPrompt;

class MockMathLevelPreviewService
{
    public const NOTICE = '[PREVIEW - MOCK RESPONSE, not real AI output]';

    public function generate(MathLevelPrompt $prompt): array
    {
        $level = $prompt->level;
        $levelTranslation = $level?->resolveTranslation([
            $prompt->locale,
            config('app.fallback_locale', 'en'),
            'en',
        ]);
        $pathTranslation = $level?->path?->resolveTranslation([
            $prompt->locale,
            config('app.fallback_locale', 'en'),
            'en',
        ]);

        $levelTitle = $levelTranslation?->title ?? $level?->slug ?? 'level';
        $pathTitle = $pathTranslation?->title ?? $level?->path?->slug ?? 'path';
        $questionCount = max(1, min(3, (int) $prompt->question_count));
        $optionCount = max(2, min(4, (int) $prompt->options_count));

        $items = collect(range(1, $questionCount))
            ->map(function (int $index) use ($levelTitle, $pathTitle, $prompt, $optionCount): array {
                $options = collect(range(1, $optionCount))
                    ->map(fn (int $optionIndex): string => "Mock option {$optionIndex} for question {$index}")
                    ->all();

                return [
                    'question' => self::NOTICE." Sample question {$index} for {$levelTitle} in {$pathTitle}.",
                    'options' => $options,
                    'answer' => $options[0],
                    'explanation' => self::NOTICE.' This is placeholder preview content generated inside CMS plumbing only.',
                    'locale' => $prompt->locale,
                ];
            })
            ->all();

        $parsedPayload = [
            'notice' => self::NOTICE,
            'generator' => 'cms-mock-preview',
            'level' => [
                'id' => $level?->id,
                'title' => $levelTitle,
                'path' => $pathTitle,
            ],
            'items' => $items,
        ];

        $requestPayload = [
            'notice' => self::NOTICE,
            'source' => 'cms-preview',
            'level_id' => $level?->id,
            'locale' => $prompt->locale,
            'question_count' => (int) $prompt->question_count,
            'options_count' => (int) $prompt->options_count,
            'prompt' => $prompt->prompt,
        ];

        return [
            'notice' => self::NOTICE,
            'logged_prompt' => self::NOTICE."\n".$prompt->prompt,
            'request_payload' => $requestPayload,
            'parsed_payload' => $parsedPayload,
            'response_raw' => self::NOTICE."\n".json_encode($parsedPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
        ];
    }
}
