<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\ImportQuestionsRequest;
use App\Services\Cms\CmsResourceRegistry;
use App\Services\Cms\CmsResourceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class QuestionImportController extends Controller
{
    public function __invoke(
        ImportQuestionsRequest $request,
        CmsResourceRegistry $registry,
        CmsResourceService $resources,
    ): JsonResponse {
        $validated = $request->validated();
        $rules = $registry->rules('questions');

        $created = DB::transaction(function () use ($validated, $rules, $resources) {
            return collect($validated['items'])->map(function (array $item, int $index) use (
                $rules,
                $resources,
                $validated
            ) {
                $data = Validator::make($item, $rules, [], [
                    '*' => "items.{$index}",
                ])->validate();

                return $resources->create(
                    'questions',
                    $data,
                    "{$validated['reason']} (import row ".($index + 1).')'
                )->getKey();
            })->all();
        });

        return response()->json([
            'message' => count($created).' questions imported.',
            'created_ids' => $created,
        ], 201);
    }
}
