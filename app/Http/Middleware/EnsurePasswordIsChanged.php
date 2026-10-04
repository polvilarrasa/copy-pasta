<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordIsChanged
{
    /**
     * Routes a member with a temporary password can still reach: the change form itself and signing out.
     */
    private const ALLOWED_ROUTES = [
        'password.temporary.edit',
        'password.temporary.update',
        'logout',
        'filament.admin.auth.logout',
        'filament.app.auth.logout',
    ];

    /**
     * Sends a member with a temporary password to the change form before anything else. Admins impersonating a
     * member are not redirected, so they can still look around.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (
            $user instanceof User
            && $user->must_change_password
            && ! is_impersonating()
            && ! $request->routeIs(self::ALLOWED_ROUTES)
        ) {
            return redirect()->route('password.temporary.edit');
        }

        return $next($request);
    }
}
