<?php

use App\Http\Controllers\Cms\CmsController;
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
})->middleware(['auth', 'verified', 'admin'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::prefix('cms-api')->middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/overview', [CmsController::class, 'overview']);
    Route::patch('/profile', [CmsController::class, 'updateCurrentProfile']);
    Route::put('/profile/password', [CmsController::class, 'updateCurrentPassword']);
    Route::get('/generation-logs', [CmsController::class, 'listGenerationLogs']);
    Route::get('/generation-logs/{generationLog}', [CmsController::class, 'showGenerationLog']);

    Route::get('/formula-categories', [CmsController::class, 'listFormulaCategories']);
    Route::get('/formula-explanations', [CmsController::class, 'listFormulaExplanations']);
    Route::get('/formula-explanations/{formula}', [CmsController::class, 'showFormulaExplanation']);
    Route::patch('/formula-explanations/{formula}/steps', [CmsController::class, 'updateFormulaExplanation']);

    Route::get('/users', [CmsController::class, 'listUsers']);
    Route::post('/users', [CmsController::class, 'storeUser']);
    Route::patch('/users/{user}', [CmsController::class, 'updateUser']);
    Route::delete('/users/{user}', [CmsController::class, 'destroyUser']);

    Route::get('/formulas', [CmsController::class, 'listFormulas']);
    Route::post('/formulas', [CmsController::class, 'storeFormula']);
    Route::patch('/formulas/{formula}', [CmsController::class, 'updateFormula']);
    Route::delete('/formulas/{formula}', [CmsController::class, 'destroyFormula']);

    Route::get('/levels', [CmsController::class, 'listLevels']);
    Route::patch('/levels/{level}', [CmsController::class, 'updateLevel']);
    Route::patch('/levels/{level}/prompt', [CmsController::class, 'updateLevelPrompt']);
    Route::post('/levels/{level}/prompt-preview', [CmsController::class, 'previewLevelPrompt'])
        ->middleware('throttle:cms-level-preview');
});

require __DIR__.'/auth.php';
