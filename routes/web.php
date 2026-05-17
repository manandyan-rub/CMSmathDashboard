<?php

use App\Http\Controllers\Cms\CmsController;
use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
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
});

require __DIR__.'/auth.php';
