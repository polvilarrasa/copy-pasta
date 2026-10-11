<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Livewire\Features\SupportDisablingBackButtonCache\SupportDisablingBackButtonCache;
use Symfony\Component\HttpFoundation\Response;

/**
 * What browsers may keep of a page that depends on who is looking at it.
 *
 *  - Pages with sensitive data (settings, statistics, notifications and the invitation) are never stored at all, so
 *    going back after leaving them shows nothing from the cache.
 *  - Every other response for a signed-in member is private and revalidated. It is not `no-store` on purpose: that
 *    would also turn off the browser's back/forward cache, and going back in the feed would lose everything loaded
 *    with "Cargar más".
 *  - Livewire, left alone, adds `no-store` to every response that renders one of its components, which is nearly every
 *    page (the bell is one). This middleware owns the cache headers, so it switches that behaviour off; going back
 *    in the feed is restored by `wire:navigate` on the cards instead.
 *  - Signing out tells the browser to drop its HTTP cache (`Clear-Site-Data`), so going back after it cannot show the
 *    previous session's pages.
 */
class SetCacheHeaders
{
    /** @var list<string> */
    private const NEVER_STORED = ['settings', 'settings/*', 'estadisticas', 'notificaciones', 'invitacion/*'];

    /** @var list<string> */
    private const LOGOUT_ROUTES = ['logout', 'filament.admin.auth.logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        SupportDisablingBackButtonCache::$disableBackButtonCache = false;

        if ($request->isMethod('POST') && $request->routeIs(self::LOGOUT_ROUTES)) {
            $response->headers->set('Clear-Site-Data', '"cache"');

            return $response;
        }

        if ($request->is(self::NEVER_STORED)) {
            $response->headers->set('Cache-Control', 'no-store, private');
        } elseif ($request->user() !== null && ! $response->headers->hasCacheControlDirective('public')) {
            $response->headers->set('Cache-Control', 'private, no-cache');
        }

        return $response;
    }
}
