<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', AuditLog::class);
        $validated = $request->validate([
            'administrator_id' => ['nullable', 'integer', 'exists:users,id'],
            'action' => ['nullable', 'string', 'max:120'],
            'entity_type' => ['nullable', 'string', 'max:160'],
            'entity_id' => ['nullable', 'integer'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
            'per_page' => ['nullable', 'integer', 'min:5', 'max:100'],
        ]);
        $query = AuditLog::query()->with('administrator:id,name,email');
        foreach (['administrator_id', 'action', 'entity_type', 'entity_id'] as $filter) {
            if (isset($validated[$filter]) && $validated[$filter] !== '') {
                $filter === 'action'
                    ? $query->where('action', 'like', '%'.$validated[$filter].'%')
                    : $query->where($filter, $validated[$filter]);
            }
        }
        if (!empty($validated['date_from'])) {
            $query->where('created_at', '>=', $validated['date_from'].' 00:00:00');
        }
        if (!empty($validated['date_to'])) {
            $query->where('created_at', '<=', $validated['date_to'].' 23:59:59');
        }
        $logs = $query->latest()->paginate($validated['per_page'] ?? 30);

        return response()->json([
            'items' => $logs->items(),
            'pagination' => [
                'page' => $logs->currentPage(),
                'last_page' => $logs->lastPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    public function show(AuditLog $auditLog): JsonResponse
    {
        Gate::authorize('view', $auditLog);

        return response()->json([
            'item' => $auditLog->load('administrator:id,name,email'),
        ]);
    }
}
