<?php

declare(strict_types=1);

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Support\ServiceWorkerAssets;
use Illuminate\Http\Response;

class ServiceWorkerController extends Controller
{
    /**
     * The service worker, served from the root so its scope is the whole site. Browsers revalidate it on every
     * navigation, so it is never cached. With the kill switch on it serves a worker that removes itself.
     */
    public function __invoke(ServiceWorkerAssets $assets): Response
    {
        $script = config('pwa.kill_switch')
            ? view('pwa.service-worker-kill')->render()
            : view('pwa.service-worker', [
                'version' => $assets->version(),
                'precache' => $assets->precache(),
                'offlineUrl' => route('pwa.offline', absolute: false),
            ])->render();

        return response($script, 200, [
            'Content-Type' => 'text/javascript; charset=utf-8',
            'Cache-Control' => 'no-cache',
            'Service-Worker-Allowed' => '/',
        ]);
    }
}
