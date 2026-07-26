<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cms\CmsDashboardRequest;
use App\Services\Cms\DashboardService;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function __invoke(CmsDashboardRequest $request, DashboardService $dashboard): JsonResponse
    {
        $validated = $request->validated();

        return response()->json($dashboard->get(
            $validated['preset'] ?? 'last_30_days',
            $validated['date_from'] ?? null,
            $validated['date_to'] ?? null,
            $validated['locale'] ?? config('app.locale', 'en'),
        ));
    }
}
