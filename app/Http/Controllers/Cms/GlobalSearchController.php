<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Services\Cms\GlobalSearchService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GlobalSearchController extends Controller
{
    public function __invoke(Request $request, GlobalSearchService $search): JsonResponse
    {
        $validated = $request->validate([
            'q' => ['required', 'string', 'min:2', 'max:255'],
            'locale' => ['nullable', 'in:'.implode(',', config('cms.locales', ['en']))],
            'limit' => ['nullable', 'integer', 'min:1', 'max:10'],
        ]);

        return response()->json([
            'groups' => $search->search(
                $request->user(),
                $validated['q'],
                $validated['locale'] ?? config('app.locale', 'en'),
                $validated['limit'] ?? 5,
            ),
        ]);
    }
}
