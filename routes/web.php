<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\NewsArticleController;
use Illuminate\Support\Facades\Route;

Route::get('/', [NewsArticleController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    
    // News routes
    Route::get('/news', [NewsArticleController::class, 'index'])->name('news.index');
    Route::post('/news/{article}/approval', [NewsArticleController::class, 'updateApprovalStatus'])
        ->name('news.approval')
        ->middleware('web');
    Route::get('/news/{article}', [NewsArticleController::class, 'show'])->name('news.show');
});

require __DIR__.'/auth.php';
