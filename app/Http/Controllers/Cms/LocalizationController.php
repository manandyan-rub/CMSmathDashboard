<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\UpdateTranslationRequest;
use App\Models\AiPrompt;
use App\Services\Cms\AiPromptRunnerService;
use App\Services\Cms\LocalizationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class LocalizationController extends Controller
{
    public function overview(Request $request, LocalizationService $localization): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('translations.view'), 403);

        return response()->json([
            'locales' => config('cms.locales', ['en']),
            'source_locale' => config('cms.source_locale', 'en'),
            'entities' => $localization->overview(),
        ]);
    }

    public function index(Request $request, LocalizationService $localization): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('translations.view'), 403);
        $validated = $request->validate([
            'entity_type' => ['required', 'in:'.implode(',', $localization->types())],
            'locale' => ['required', 'in:'.implode(',', config('cms.locales', ['en']))],
            'missing' => ['nullable', 'boolean'],
            'outdated' => ['nullable', 'boolean'],
            'search' => ['nullable', 'string', 'max:255'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $items = $localization->paginate(
            $validated['entity_type'],
            $validated['locale'],
            (bool) ($validated['missing'] ?? false),
            (bool) ($validated['outdated'] ?? false),
            trim((string) ($validated['search'] ?? '')),
            (int) ($validated['per_page'] ?? 20),
        );

        return response()->json([
            'items' => collect($items->items())->map(
                fn ($model) => $localization->serialize($model, $validated['entity_type'])
            ),
            'pagination' => [
                'page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function update(
        UpdateTranslationRequest $request,
        string $entityType,
        int $id,
        LocalizationService $localization,
    ): JsonResponse {
        $model = $localization->update(
            $entityType,
            $id,
            $request->validated('locale'),
            $request->validated('values'),
            (bool) $request->validated('reviewed', false),
            (bool) $request->validated('machine_draft', false),
            $request->validated('reason')
        );

        return response()->json([
            'message' => 'Translation updated.',
            'item' => $localization->serialize($model, $entityType),
        ]);
    }

    public function copySource(
        Request $request,
        string $entityType,
        int $id,
        LocalizationService $localization,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('translations.manage'), 403);
        $validated = $request->validate([
            'target_locale' => ['required', 'in:'.implode(',', config('cms.locales', ['en']))],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $model = $localization->copySource(
            $entityType,
            $id,
            $validated['target_locale'],
            $validated['reason']
        );

        return response()->json([
            'message' => 'Source locale copied as an unreviewed draft.',
            'item' => $localization->serialize($model, $entityType),
        ]);
    }

    public function machineDraft(
        Request $request,
        string $entityType,
        int $id,
        LocalizationService $localization,
        AiPromptRunnerService $runner,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('translations.manage'), 403);
        $validated = $request->validate([
            'target_locale' => ['required', 'in:'.implode(',', config('cms.locales', ['en']))],
        ]);
        $item = $localization->findSerialized($entityType, $id);
        $sourceLocale = config('cms.source_locale', 'en');
        $source = $item['translations'][$sourceLocale] ?? null;
        abort_unless($source, 422, 'The source locale is missing.');
        $prompt = AiPrompt::query()
            ->where('feature', 'translation_draft')
            ->where('active', true)
            ->firstOrFail();
        $log = $runner->run($prompt, [
            'entity_type' => $entityType,
            'source_locale' => $sourceLocale,
            'target_locale' => $validated['target_locale'],
            'content' => $source,
        ], 'cms_translation_draft');
        abort_unless($log->status === 'completed', 422, $log->error ?: 'Translation generation failed.');
        $parsed = json_decode($log->parsed_json ?: '{}', true);
        $values = $parsed['output'] ?? null;
        abort_unless(is_array($values), 422, 'The translation prompt did not return structured fields.');
        $model = $localization->update(
            $entityType,
            $id,
            $validated['target_locale'],
            $values,
            false,
            true,
            "Machine translation draft from generation #{$log->id}"
        );

        return response()->json([
            'message' => 'Machine translation saved as an unreviewed draft.',
            'item' => $localization->serialize($model, $entityType),
            'generation_log_id' => $log->id,
        ]);
    }

    public function import(Request $request, LocalizationService $localization): JsonResponse
    {
        abort_unless($request->user()->hasCmsPermission('translations.manage'), 403);
        $validated = $request->validate([
            'items' => ['required', 'array', 'min:1', 'max:1000'],
            'items.*.entity_type' => ['required', 'in:'.implode(',', $localization->types())],
            'items.*.entity_id' => ['required', 'integer'],
            'items.*.locale' => ['required', 'in:'.implode(',', config('cms.locales', ['en']))],
            'items.*.values' => ['required', 'array'],
            'items.*.reviewed' => ['nullable', 'boolean'],
            'items.*.machine_draft' => ['nullable', 'boolean'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $count = $localization->import($validated['items'], $validated['reason']);

        return response()->json(['message' => "{$count} translations imported.", 'count' => $count]);
    }

    public function export(Request $request, LocalizationService $localization): StreamedResponse
    {
        abort_unless($request->user()->hasCmsPermission('translations.view'), 403);
        abort_unless($request->user()->hasCmsPermission('exports.create'), 403);
        $entityType = $request->validate([
            'entity_type' => ['required', 'in:'.implode(',', $localization->types())],
        ])['entity_type'];

        return response()->streamDownload(function () use ($entityType, $localization) {
            echo '[';
            $first = true;
            foreach ($localization->exportItems($entityType) as $model) {
                if (!$first) {
                    echo ',';
                }
                echo json_encode(
                    $localization->serialize($model, $entityType),
                    JSON_UNESCAPED_UNICODE
                );
                $first = false;
            }
            echo ']';
        }, "translations-{$entityType}.json", ['Content-Type' => 'application/json']);
    }
}
