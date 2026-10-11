<?php

declare(strict_types=1);

use App\Http\Controllers\Public\ManifestController;
use App\Http\Controllers\Public\OfflineController;
use App\Http\Controllers\Public\ServiceWorkerController;
use Illuminate\Support\Facades\Route;

/*
 * Outside the `web` group on purpose: no session, no cookies and no database, so these three answer the same for
 * everyone and the service worker can cache the offline page. bootstrap/app.php registers the file with
 * SecurityHeaders only.
 */
Route::get('/manifest.webmanifest', ManifestController::class)->name('pwa.manifest');
Route::get('/sw.js', ServiceWorkerController::class)->name('pwa.service-worker');
Route::get('/sin-conexion', OfflineController::class)->name('pwa.offline');
