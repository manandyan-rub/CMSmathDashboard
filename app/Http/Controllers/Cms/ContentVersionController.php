<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\ContentVersion;
use App\Services\Cms\CmsResourceRegistry;
use App\Services\Cms\CmsResourceService;
use App\Services\Cms\ContentVersionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContentVersionController extends Controller
{
    public function index(
        Request $request,
        string $resource,
        int $id,
        CmsResourceRegistry $registry,
        CmsResourceService $resources,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission($registry->viewPermission($resource)), 403);
        $model = $resources->find($resource, $id);

        $versions = ContentVersion::query()
            ->with('author:id,name,email')
            ->where('versionable_type', $model->getMorphClass())
            ->where('versionable_id', $model->getKey())
            ->latest('version_number')
            ->paginate(20);

        return response()->json([
            'items' => $versions->items(),
            'pagination' => [
                'page' => $versions->currentPage(),
                'last_page' => $versions->lastPage(),
                'total' => $versions->total(),
            ],
        ]);
    }

    public function restore(
        Request $request,
        ContentVersion $version,
        ContentVersionService $versions,
        CmsResourceRegistry $registry,
        CmsResourceService $resources,
    ): JsonResponse {
        abort_unless($request->user()->hasCmsPermission('content.publish'), 403);
        $validated = $request->validate([
            'confirmed' => ['accepted'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);

        $versionable = $version->versionable()->firstOrFail();
        $resource = $registry->resourceForModel($versionable);
        $model = $resource
            ? $resources->restoreVersion($resource, $versionable, $version, $validated['reason'])
            : $versions->restore($version, $validated['reason']);

        return response()->json([
            'message' => 'Previous version restored.',
            'item' => $model,
        ]);
    }
}
