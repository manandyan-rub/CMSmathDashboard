<?php

namespace App\Http\Controllers\Cms;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CmsMetaController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user()->load('country:id,name,iso_code');

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
                'locale' => $user->locale,
                'country' => $user->country,
                'permissions' => $user->cmsPermissionKeys(),
            ],
            'locales' => config('cms.locales', ['en']),
            'source_locale' => config('cms.source_locale', 'en'),
            'countries' => Country::query()->orderBy('name')->get(['id', 'name', 'iso_code']),
            'permissions' => Permission::query()
                ->orderBy('group')
                ->orderBy('key')
                ->get(['id', 'key', 'label', 'group']),
            'resources' => collect(config('cms_resources', []))->map(
                fn (array $definition, string $key) => [
                    'key' => $key,
                    'title' => $definition['title'],
                    'view_permission' => $definition['view_permission']
                        ?? $definition['permission'].'.view',
                    'manage_permission' => $definition['manage_permission']
                        ?? $definition['permission'].'.manage',
                ]
            )->values(),
        ]);
    }
}
