<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStaffHasTwoFactor
{
    /**
     * Staff reach the admin panel only with two-factor authentication enabled. Others are sent to set it up.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user instanceof User && $user->isStaff() && ! is_impersonating() && ! $user->hasEnabledTwoFactorAuthentication()) {
            return redirect()->route('security.edit')->with('status', __('admin.two_factor_required'));
        }

        return $next($request);
    }
}
