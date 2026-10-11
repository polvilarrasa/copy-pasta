<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ListActiveTags;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Members who were never offered the welcome screen are sent to it the first time they open the feed. Offering it
 * stamps `onboarded_at`, so it happens once even if they leave without choosing. Staff, impersonated sessions and
 * anything that is not a plain page view are left alone. With fewer active tags than the minimum there is nothing to pick,
 * so the screen is not offered and the member is not stamped.
 */
class RedirectToOnboarding
{
    /**
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $request->isMethod('GET') || ! $user instanceof User || $user->onboarded_at !== null
            || $user->isStaff() || is_impersonating() || ! app(ListActiveTags::class)->canOfferOnboarding()) {
            return $next($request);
        }

        $user->forceFill(['onboarded_at' => now()])->save();

        return redirect()->route('welcome');
    }
}
