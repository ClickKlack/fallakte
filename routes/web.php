<?php

use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\EpisodeFormController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StatisticsController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', [EpisodeController::class, 'index'])->name('episodes.index');
    Route::patch('/episodes/{episode}/seen', [EpisodeController::class, 'updateSeen'])->name('episodes.seen');
    Route::get('/episodes/create', [EpisodeFormController::class, 'create'])->name('episodes.create');
    Route::post('/episodes', [EpisodeFormController::class, 'store'])->name('episodes.store');
    Route::get('/episodes/{episode}/edit', [EpisodeFormController::class, 'edit'])->name('episodes.edit');
    Route::put('/episodes/{episode}', [EpisodeFormController::class, 'update'])->name('episodes.update');
    Route::delete('/episodes/{episode}/protection/{field}', [EpisodeFormController::class, 'resetProtection'])
        ->name('episodes.protection.destroy');

    Route::get('/teams', [TeamController::class, 'index'])->name('teams.index');
    Route::put('/teams/{team}', [TeamController::class, 'update'])->name('teams.update');
    Route::post('/teams/{team}/merge', [TeamController::class, 'merge'])->name('teams.merge');

    Route::get('/statistik', StatisticsController::class)->name('dashboard');

    // Übersicht der UI-Bausteine, nur in der lokalen Entwicklung.
    if (app()->isLocal()) {
        Route::get('/styleguide', fn () => Inertia::render('Styleguide'))->name('styleguide');
    }

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
