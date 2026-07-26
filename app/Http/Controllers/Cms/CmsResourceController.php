<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CmsBulkActionRequest;
use App\Http\Requests\Cms\CmsResourceActionRequest;
use App\Http\Requests\Cms\CmsResourceIndexRequest;
use App\Http\Requests\Cms\ReorderCmsResourceRequest;
use App\Http\Requests\Cms\StoreCmsResourceRequest;
use App\Http\Requests\Cms\UpdateCmsResourceRequest;
use App\Models\CalculatorContent;
use App\Models\PracticeTemplate;
use App\Services\Cms\CmsResourceRegistry;
use App\Services\Cms\CmsResourceService;
use App\Services\Cms\PracticeTemplatePreviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsResourceController extends Controller
{
    public function schema(
        Request $request,
        string $resource,
        CmsResourceRegistry $registry,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission($registry->viewPermission($resource)), 403);

        return response()->json(['resource' => $registry->schema($resource)]);
    }

    public function index(
        CmsResourceIndexRequest $request,
        string $resource,
        CmsResourceService $service,
    ): JsonResponse {
        $paginator = $service->paginate($resource, $request->validated());
        $locale = $request->validated('locale', config('app.locale', 'en'));

        return response()->json([
            'items' => collect($paginator->items())
                ->map(fn ($model) => $service->serialize($model, $locale))
                ->values(),
            'pagination' => $this->pagination($paginator),
        ]);
    }

    public function show(
        Request $request,
        string $resource,
        int $id,
        CmsResourceRegistry $registry,
        CmsResourceService $service,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission($registry->viewPermission($resource)), 403);
        $locale = $request->query('locale', config('app.locale', 'en'));

        return response()->json([
            'item' => $service->serialize($service->find($resource, $id), $locale),
        ]);
    }

    public function store(
        StoreCmsResourceRequest $request,
        string $resource,
        CmsResourceService $service,
    ): JsonResponse {
        $data = $request->safe()->except('reason');
        $model = $service->create($resource, $data, $request->validated('reason'));

        return response()->json([
            'message' => 'Content created.',
            'item' => $service->serialize($model, $request->input('locale', 'en')),
        ], 201);
    }

    public function update(
        UpdateCmsResourceRequest $request,
        string $resource,
        int $id,
        CmsResourceService $service,
    ): JsonResponse {
        $model = $service->find($resource, $id);
        $data = $request->safe()->except('reason');
        $model = $service->update($resource, $model, $data, $request->validated('reason'));

        return response()->json([
            'message' => 'Content updated.',
            'item' => $service->serialize($model, $request->input('locale', 'en')),
        ]);
    }

    public function duplicate(
        Request $request,
        string $resource,
        int $id,
        CmsResourceRegistry $registry,
        CmsResourceService $service,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission($registry->managePermission($resource)), 403);
        $copy = $service->duplicate($resource, $service->find($resource, $id));

        return response()->json([
            'message' => 'Content duplicated as a draft.',
            'item' => $service->serialize($copy, $request->input('locale', 'en')),
        ], 201);
    }

    public function destroy(
        CmsResourceActionRequest $request,
        string $resource,
        int $id,
        CmsResourceService $service,
    ): JsonResponse {
        $service->destroy(
            $resource,
            $service->find($resource, $id),
            $request->validated('mode', 'archive'),
            $request->validated('reason')
        );

        return response()->json(['message' => 'Content removed from the active catalog.']);
    }

    public function reorder(
        ReorderCmsResourceRequest $request,
        string $resource,
        CmsResourceService $service,
    ): JsonResponse {
        $service->reorder($resource, $request->validated('items'));

        return response()->json(['message' => 'Display order updated.']);
    }

    public function bulk(
        CmsBulkActionRequest $request,
        string $resource,
        CmsResourceService $service,
    ): JsonResponse {
        $count = $service->bulk(
            $resource,
            $request->validated('ids'),
            $request->validated('action'),
            $request->validated('reason')
        );

        return response()->json(['message' => "{$count} items updated.", 'updated' => $count]);
    }

    public function preview(
        Request $request,
        string $resource,
        int $id,
        CmsResourceRegistry $registry,
        CmsResourceService $service,
        PracticeTemplatePreviewService $practicePreview,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission($registry->viewPermission($resource)), 403);
        $model = $service->find($resource, $id);

        if ($model instanceof PracticeTemplate) {
            return response()->json(['preview' => $practicePreview->preview($model)]);
        }
        if ($model instanceof CalculatorContent) {
            return response()->json([
                'preview' => [
                    'key' => $model->key,
                    'category' => $model->category,
                    'translation' => $service->serialize(
                        $model,
                        $request->input('locale', config('app.locale', 'en'))
                    )['translation'],
                    'fields' => $model->fields,
                    'supported_units' => $model->supported_units,
                    'default_unit' => $model->default_unit,
                    'default_precision' => $model->default_precision,
                    'notice' => 'Metadata preview only. Solver logic remains in MathDashboard.',
                ],
            ]);
        }

        return response()->json([
            'preview' => $service->serialize($model, $request->input('locale', 'en')),
        ]);
    }

    private function pagination($paginator): array
    {
        return [
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'from' => $paginator->firstItem(),
            'to' => $paginator->lastItem(),
        ];
    }
}
