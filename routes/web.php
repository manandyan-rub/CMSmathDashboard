<?php

use App\Http\Controllers\Cms\AiAdministrationController;
use App\Http\Controllers\Cms\AuditLogController;
use App\Http\Controllers\Cms\CmsMetaController;
use App\Http\Controllers\Cms\CmsResourceController;
use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\Cms\ContentVersionController;
use App\Http\Controllers\Cms\DashboardController;
use App\Http\Controllers\Cms\ExerciseStudioController;
use App\Http\Controllers\Cms\ExportController;
use App\Http\Controllers\Cms\GlobalSearchController;
use App\Http\Controllers\Cms\LearningAnalyticsController;
use App\Http\Controllers\Cms\LocalizationController;
use App\Http\Controllers\Cms\PlatformSettingsController;
use App\Http\Controllers\Cms\PublishingController;
use App\Http\Controllers\Cms\QuestionImportController;
use App\Http\Controllers\Cms\UserAdministrationController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified', 'cms.access'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/api/internal/v1/platform-config', [PlatformSettingsController::class, 'publicConfig'])
    ->middleware('throttle:internal-api');

Route::prefix('cms-api')->middleware(['auth', 'verified', 'cms.access'])->group(function () {
    Route::get('/meta', CmsMetaController::class);
    Route::get('/dashboard-analytics', DashboardController::class)
        ->middleware('permission:dashboard.view');
    Route::get('/overview', [CmsController::class, 'overview'])
        ->middleware('permission:dashboard.view');
    Route::patch('/profile', [CmsController::class, 'updateCurrentProfile']);
    Route::put('/profile/password', [CmsController::class, 'updateCurrentPassword']);
    Route::get('/search', GlobalSearchController::class)
        ->middleware('throttle:cms-global-search');

    Route::get('/generation-logs', [CmsController::class, 'listGenerationLogs'])
        ->middleware('permission:ai.view');
    Route::get('/generation-logs/{generationLog}', [CmsController::class, 'showGenerationLog'])
        ->middleware('permission:ai.view');

    Route::get('/formula-categories', [CmsController::class, 'listFormulaCategories'])
        ->middleware('permission:formulas.view');
    Route::get('/formula-explanations', [CmsController::class, 'listFormulaExplanations'])
        ->middleware('permission:formulas.view');
    Route::get('/formula-explanations/{formula}', [CmsController::class, 'showFormulaExplanation'])
        ->middleware('permission:formulas.view');
    Route::patch('/formula-explanations/{formula}/steps', [CmsController::class, 'updateFormulaExplanation'])
        ->middleware('permission:formulas.manage');

    Route::get('/users', [UserAdministrationController::class, 'index']);
    Route::post('/users', [CmsController::class, 'storeUser'])->middleware('permission:users.manage');
    Route::get('/users/{user}', [UserAdministrationController::class, 'show']);
    Route::patch('/users/{user}', [CmsController::class, 'updateUser'])->middleware('permission:users.manage');
    Route::delete('/users/{user}', [CmsController::class, 'destroyUser'])->middleware('permission:users.manage');
    Route::post('/users/{user}/actions', [UserAdministrationController::class, 'action']);
    Route::post('/users/{user}/xp-adjustments', [UserAdministrationController::class, 'adjustXp']);
    Route::post('/users/{user}/progress-resets', [UserAdministrationController::class, 'resetProgress']);
    Route::get('/users/{user}/progress', [LearningAnalyticsController::class, 'studentProgress']);
    Route::patch('/users/{user}/lecturer', [UserAdministrationController::class, 'updateLecturer']);
    Route::put('/users/{user}/permissions', [UserAdministrationController::class, 'permissions']);
    Route::get('/users/{user}/export', [UserAdministrationController::class, 'export']);

    Route::get('/formulas', [CmsController::class, 'listFormulas'])
        ->middleware('permission:formulas.view');
    Route::post('/formulas', [CmsController::class, 'storeFormula'])
        ->middleware('permission:formulas.manage');
    Route::patch('/formulas/{formula}', [CmsController::class, 'updateFormula'])
        ->middleware('permission:formulas.manage');
    Route::delete('/formulas/{formula}', [CmsController::class, 'destroyFormula'])
        ->middleware('permission:formulas.manage');

    Route::get('/levels', [CmsController::class, 'listLevels'])
        ->middleware('permission:levels.view');
    Route::patch('/levels/{level}', [CmsController::class, 'updateLevel'])
        ->middleware('permission:levels.manage');
    Route::patch('/levels/{level}/prompt', [CmsController::class, 'updateLevelPrompt'])
        ->middleware('permission:levels.manage');
    Route::post('/levels/{level}/prompt-preview', [CmsController::class, 'previewLevelPrompt'])
        ->middleware(['permission:levels.manage', 'throttle:cms-level-preview']);
    Route::get('/levels/{level}/analytics', [LearningAnalyticsController::class, 'level']);
    Route::post('/questions/import', QuestionImportController::class);

    Route::get('/resources/{resource}/schema', [CmsResourceController::class, 'schema']);
    Route::get('/resources/{resource}', [CmsResourceController::class, 'index']);
    Route::post('/resources/{resource}', [CmsResourceController::class, 'store']);
    Route::post('/resources/{resource}/reorder', [CmsResourceController::class, 'reorder']);
    Route::post('/resources/{resource}/bulk', [CmsResourceController::class, 'bulk']);
    Route::get('/resources/{resource}/{id}', [CmsResourceController::class, 'show'])->whereNumber('id');
    Route::patch('/resources/{resource}/{id}', [CmsResourceController::class, 'update'])->whereNumber('id');
    Route::delete('/resources/{resource}/{id}', [CmsResourceController::class, 'destroy'])->whereNumber('id');
    Route::post('/resources/{resource}/{id}/duplicate', [CmsResourceController::class, 'duplicate'])->whereNumber('id');
    Route::get('/resources/{resource}/{id}/preview', [CmsResourceController::class, 'preview'])->whereNumber('id');
    Route::put('/resources/{resource}/{id}/publishing', [PublishingController::class, 'update'])->whereNumber('id');
    Route::get('/resources/{resource}/{id}/versions', [ContentVersionController::class, 'index'])->whereNumber('id');
    Route::post('/versions/{version}/restore', [ContentVersionController::class, 'restore']);

    Route::get('/exercise-documents', [ExerciseStudioController::class, 'index']);
    Route::get('/exercise-documents/{document}', [ExerciseStudioController::class, 'show']);
    Route::post('/exercise-documents/{document}/actions', [ExerciseStudioController::class, 'action']);
    Route::get('/exercise-documents/{document}/export', [ExerciseStudioController::class, 'export']);

    Route::post('/ai/prompts/{prompt}/versions', [AiAdministrationController::class, 'storeVersion']);
    Route::put('/ai/prompts/{prompt}/versions/{version}/activate', [AiAdministrationController::class, 'activateVersion']);
    Route::get('/ai/prompts/{prompt}/compare/{left}/{right}', [AiAdministrationController::class, 'compare']);
    Route::post('/ai/prompts/{prompt}/test', [AiAdministrationController::class, 'test'])
        ->middleware('throttle:cms-ai-test');
    Route::post('/ai/generations/{generationLog}/retry', [AiAdministrationController::class, 'retry'])
        ->middleware('throttle:cms-ai-test');
    Route::get('/ai/analytics', [AiAdministrationController::class, 'analytics']);

    Route::get('/localization/overview', [LocalizationController::class, 'overview']);
    Route::get('/localization/items', [LocalizationController::class, 'index']);
    Route::put('/localization/{entityType}/{id}', [LocalizationController::class, 'update'])->whereNumber('id');
    Route::post('/localization/{entityType}/{id}/copy-source', [LocalizationController::class, 'copySource'])->whereNumber('id');
    Route::post('/localization/{entityType}/{id}/machine-draft', [LocalizationController::class, 'machineDraft'])
        ->middleware('throttle:cms-ai-test')
        ->whereNumber('id');
    Route::post('/localization/import', [LocalizationController::class, 'import']);
    Route::get('/localization/export', [LocalizationController::class, 'export']);

    Route::get('/games/{game}/analytics', [LearningAnalyticsController::class, 'game']);
    Route::get('/gamification/hall-of-fame', [LearningAnalyticsController::class, 'hallOfFame']);

    Route::get('/platform-settings', [PlatformSettingsController::class, 'index']);
    Route::patch('/platform-settings/{setting}', [PlatformSettingsController::class, 'update']);
    Route::get('/audit-logs', [AuditLogController::class, 'index']);
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show']);
    Route::get('/exports/{resource}', ExportController::class);
});

require __DIR__.'/auth.php';
