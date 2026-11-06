<?php

use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::middleware('auth')->group(function () {
    Route::get('/', [EpisodeController::class, 'index'])->name('episodes.index');
    Route::patch('/episodes/{episode}/seen', [EpisodeController::class, 'updateSeen'])->name('episodes.seen');

    // Statistik-Dashboard: wird in Schritt 9 ausgebaut.
    Route::get('/dashboard', function () {
        return Inertia::render('Dashboard');
    })->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
});

require __DIR__.'/auth.php';
