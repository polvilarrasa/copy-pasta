<?php

use App\Http\Controllers\Auth\InvitationController;
use App\Http\Controllers\Impersonation\LeaveImpersonationController;
use App\Http\Controllers\Public\AnonymousNoticeController;
use App\Http\Controllers\Public\CopypastaController;
use App\Http\Controllers\Public\CopypastaCopyController;
use App\Http\Controllers\Public\CopypastaEditController;
use App\Http\Controllers\Public\CopypastaFavoriteController;
use App\Http\Controllers\Public\CopypastaFolderController;
use App\Http\Controllers\Public\CopypastaReportController;
use App\Http\Controllers\Public\CopypastaShareController;
use App\Http\Controllers\Public\CopypastaVoteController;
use App\Http\Controllers\Public\DismissController;
use App\Http\Controllers\Public\FeedController;
use App\Http\Controllers\Public\FolderController;
use App\Http\Controllers\Public\MyCopypastasController;
use App\Http\Controllers\Public\NotificationController;
use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Http\Controllers\Public\ProfileController;
use App\Http\Controllers\Public\PublicFolderController;
use App\Http\Controllers\Public\PublishCopypastaController;
use App\Http\Controllers\Public\RobotsController;
use App\Http\Controllers\Public\SitemapController;
use App\Http\Controllers\Public\StatsController;
use App\Http\Controllers\Public\ThemeController;
use App\Http\Controllers\Public\WelcomeController;
use App\Http\Middleware\RedirectToOnboarding;
use Illuminate\Support\Facades\Route;

// A member who was never offered the welcome screen is sent to it the first time they open any of the feeds.
Route::middleware(RedirectToOnboarding::class)->group(function () {
    Route::get('/', [FeedController::class, 'home'])->name('home');
    Route::get('/top/semana', [FeedController::class, 'topWeek'])->name('feed.top-week');
    Route::get('/top/mes', [FeedController::class, 'topMonth'])->name('feed.top-month');
    Route::get('/top', [FeedController::class, 'topAll'])->name('feed.top-all');
    Route::get('/nuevos', [FeedController::class, 'newest'])->name('feed.newest');
    Route::get('/etiqueta/{slug}', [FeedController::class, 'tag'])->name('feed.tag');
});

// Declared before the detail route: its optional slug would otherwise swallow GET /c/{id}/carpetas and /c/{id}/editar.
Route::middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'index'])->name('copypastas.folders.index');
    Route::put('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'update'])->name('copypastas.folders.sync');
    Route::post('/c/{copypasta}/carpetas', [CopypastaFolderController::class, 'store'])->name('copypastas.folders.store');
});

Route::middleware(['auth', 'throttle:120,1'])->group(function () {
    Route::post('/c/{copypasta}/descartar', [DismissController::class, 'store'])->name('copypastas.dismiss');
    Route::delete('/c/{copypasta}/descartar', [DismissController::class, 'destroy'])->name('copypastas.dismiss.undo');
});

Route::middleware('auth')->group(function () {
    Route::get('/c/{copypasta}/editar', [CopypastaEditController::class, 'edit'])->name('copypastas.edit');
});

Route::get('/c/{copypasta}/{slug?}', [CopypastaController::class, 'show'])->name('copypastas.show');
Route::post('/c/{copypasta}/copia', CopypastaCopyController::class)
    ->middleware('throttle:120,1')
    ->name('copypastas.copy');

Route::post('/c/{copypasta}/comparte', CopypastaShareController::class)
    ->middleware('throttle:120,1')
    ->name('copypastas.share');

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

Route::post('/tema', ThemeController::class)
    ->middleware('throttle:60,1')
    ->name('theme.update');

if (app()->environment(['local', 'testing'])) {
    Route::view('/_componentes', 'public.componentes')->name('components.showcase');
}

Route::get('/aviso/{copypasta}', [AnonymousNoticeController::class, 'create'])->name('notice.create');
Route::post('/aviso/{copypasta}', [AnonymousNoticeController::class, 'store'])
    ->middleware('throttle:5,60')
    ->name('notice.store');

Route::view('/normas', 'public.normas')->name('normas');
Route::view('/privacidad', 'public.privacidad')->name('privacy');
Route::view('/cookies', 'public.cookies')->name('cookies');

Route::get('/col/{publicId}', [PublicFolderController::class, 'show'])
    ->where('publicId', '[0-9A-Za-z]{26}')
    ->name('folders.public');

Route::get('/u/{username}', [ProfileController::class, 'show'])->name('profile.show');

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

Route::middleware('auth')->group(function () {
    Route::get('/bienvenida', [WelcomeController::class, 'show'])->name('welcome');
    Route::get('/mis-copypastas', [MyCopypastasController::class, 'index'])->name('copypastas.mine');
    Route::get('/publicar', [PublishCopypastaController::class, 'create'])->name('copypastas.create');
    Route::get('/carpetas', [FolderController::class, 'index'])->name('folders.index');
    Route::get('/carpetas/{folder}', [FolderController::class, 'show'])->name('folders.show');
    Route::get('/estadisticas', [StatsController::class, 'show'])->name('stats.show');
    Route::get('/notificaciones', [NotificationController::class, 'index'])->name('notifications.index');
});

// The Filament /app panel is gone (Fase 15); these URLs keep working for anyone with an old link or bookmark.
Route::redirect('/app', '/estadisticas', 301);
Route::redirect('/app/copypastas', '/mis-copypastas', 301);
Route::redirect('/app/copypastas/create', '/publicar', 301);
Route::redirect('/app/copypastas/{record}/edit', '/c/{record}/editar', 301);
Route::redirect('/app/carpetas', '/carpetas', 301);
Route::redirect('/app/carpetas/{record}', '/carpetas/{record}', 301);

require __DIR__.'/settings.php';
