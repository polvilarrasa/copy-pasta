<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BlockDuringImpersonation
{
    /**
     * Two-factor and passkey routes belong to the account's owner. An admin acting as a member cannot reach them.
     */
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(is_impersonating(), 403);

        return $next($request);
    }
}
