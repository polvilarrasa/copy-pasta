<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Impersonation\LeaveImpersonationController;
use App\Http\Controllers\Public\AnonymousNoticeController;
use App\Http\Controllers\Public\CopypastaController;
use App\Http\Controllers\Public\CopypastaCopyController;
use App\Http\Controllers\Public\CopypastaFavoriteController;
use App\Http\Controllers\Public\CopypastaFolderController;
use App\Http\Controllers\Public\CopypastaReportController;
use App\Http\Controllers\Public\CopypastaVoteController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use Illuminate\Support\Facades\Route;

Route::get('/', [FeedController::class, 'home'])->name('home');
Route::get('/top/semana', [FeedController::class, 'topWeek'])->name('feed.top-week');
Route::get('/top/mes', [FeedController::class, 'topMonth'])->name('feed.top-month');
Route::get('/top', [FeedController::class, 'topAll'])->name('feed.top-all');
Route::get('/nuevos', [FeedController::class, 'newest'])->name('feed.newest');
Route::get('/etiqueta/{slug}', [FeedController::class, 'tag'])->name('feed.tag');

// Declared before the detail route: its optional slug would otherwise swallow GET /c/{id}/carpetas.
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'index'])->name('copypastas.folders.index');
    Route::put('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'update'])->name('copypastas.folders.sync');
    Route::post('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'store'])->name('copypastas.folders.store');
});

Route::get('/c/{copypasta}/{slug?}', [CopypastaController::class, 'show'])->name('copypastas.show');
Route::post('/c/{copypasta}/copia', CopypastaCopyController::class)
    ->middleware('throttle:120,1')
    ->name('copypastas.copy');

Route::post('/c/{copypasta}/voto', CopypastaVoteController::class)
    ->middleware(['auth', 'throttle:votes'])
    ->name('copypastas.vote');
Route::post('/c/{copypasta}/favorito', CopypastaFavoriteController::class)
    ->middleware(['auth', 'throttle:120,1'])
    ->name('copypastas.favorite');

Route::post('/c/{copypasta}/reporte', CopypastaReportController::class)
    ->middleware('auth')
    ->name('copypastas.report');

Route::post('/nsfw/confirmar', NsfwConfirmationController::class)->name('nsfw.confirm');

Route::get('/aviso/{copypasta}', [AnonymousNoticeController::class, 'create'])->name('notice.create');
Route::post('/aviso/{copypasta}', [AnonymousNoticeController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('notice.store');

Route::view('/normas', 'public.normas')->name('normas');
Route::view('/privacidad', 'public.privacidad')->name('privacy');
Route::view('/cookies', 'public.cookies')->name('cookies');

Route::get('/sitemap.xml', SitemapController::class)->name('sitemap');
Route::get('/robots.txt', RobotsController::class)->name('robots');

Route::post('/impersonacion/salir', LeaveImpersonationController::class)
    ->middleware('auth')
    ->name('impersonation.leave');

Route::middleware('signed')->group(function () {
    Route::get('/invitacion/{user}', [InvitationController::class, 'show'])->name('invitation.show');
    Route::post('/invitacion/{user}', [InvitationController::class, 'store'])->name('invitation.store');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
