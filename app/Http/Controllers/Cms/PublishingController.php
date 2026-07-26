<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\PublishContentRequest;
use App\Services\Cms\CmsResourceRegistry;
use App\Services\Cms\CmsResourceService;
use App\Services\Cms\PublishingService;
use Illuminate\Http\JsonResponse;

class PublishingController extends Controller
{
    public function update(
        PublishContentRequest $request,
        string $resource,
        int $id,
        CmsResourceRegistry $registry,
        CmsResourceService $resources,
        PublishingService $publishing,
    ): JsonResponse {
        $model = $resources->find($resource, $id);
        abort_unless(method_exists($model, 'workflow'), 422, 'This content is not publishable.');

        $workflow = $publishing->transition(
            $model,
            $request->validated('state'),
            $request->validated('comment'),
            $request->validated('scheduled_at')
        );

        return response()->json([
            'message' => 'Publishing state updated.',
            'publishing' => $workflow,
        ]);
    }
}
