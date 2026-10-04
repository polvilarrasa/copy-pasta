<?php

use App\Http\Controllers\Public\CopypastaController;
use App\Http\Controllers\Public\CopypastaCopyController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\NsfwConfirmationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FeedController::class, 'home'])->name('home');
Route::get('/top/semana', [FeedController::class, 'topWeek'])->name('feed.top-week');
Route::get('/top/mes', [FeedController::class, 'topMonth'])->name('feed.top-month');
Route::get('/top', [FeedController::class, 'topAll'])->name('feed.top-all');
Route::get('/nuevos', [FeedController::class, 'newest'])->name('feed.newest');
Route::get('/etiqueta/{slug}', [FeedController::class, 'tag'])->name('feed.tag');

Route::get('/c/{copypasta}/{slug?}', [CopypastaController::class, 'show'])->name('copypastas.show');
Route::post('/c/{copypasta}/copia', CopypastaCopyController::class)
    ->middleware('throttle:120,1')
    ->name('copypastas.copy');

Route::post('/nsfw/confirmar', NsfwConfirmationController::class)->name('nsfw.confirm');

Route::view('/normas', 'public.normas')->name('normas');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
