<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\PlatformSetting;
use App\Services\Cms\AuditService;
use App\Services\Cms\PlatformSettingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class PlatformSettingsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        Gate::authorize('viewAny', PlatformSetting::class);

        return response()->json([
            'items' => PlatformSetting::query()->orderBy('group')->orderBy('key')->get(),
        ]);
    }

    public function update(
        Request $request,
        PlatformSetting $setting,
        PlatformSettingService $settings,
        AuditService $audit,
    ): JsonResponse {
        Gate::authorize('update', $setting);
        $validated = $request->validate([
            'value' => ['present'],
            'reason' => ['required', 'string', 'min:3', 'max:2000'],
        ]);
        $before = ['value' => $setting->value];
        $setting = $settings->update($setting, $validated['value']);
        $audit->record(
            'platform_setting.updated',
            $setting,
            $before,
            ['value' => $setting->value],
            $validated['reason']
        );

        return response()->json(['message' => 'Setting updated.', 'setting' => $setting]);
    }

    public function publicConfig(PlatformSettingService $settings): JsonResponse
    {
        return response()->json([
            'settings' => $settings->all(true),
            'feature_flags' => \App\Models\FeatureFlag::query()
                ->where('public', true)
                ->where(function ($query) {
                    $query->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                })
                ->where(function ($query) {
                    $query->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                })
                ->pluck('enabled', 'key'),
        ]);
    }
}
