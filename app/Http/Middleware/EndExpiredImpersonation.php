<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Actions\ImpersonateUser;
use App\Actions\StopImpersonating;
use Closure;
use Illuminate\Http\Request;
use Lab404\Impersonate\Services\ImpersonateManager;
use Symfony\Component\HttpFoundation\Response;

class EndExpiredImpersonation
{
    public const TIMEOUT_MINUTES = 30;

    /**
     * Ends an impersonation that outlived its window, so the admin is back in control and the end is logged.
     * An impersonation without a start time counts as expired.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (is_impersonating() && $this->hasExpired()) {
            app(StopImpersonating::class)->handle(app(ImpersonateManager::class), 'expired');
        }

        return $next($request);
    }

    private function hasExpired(): bool
    {
        $startedAt = session(ImpersonateUser::STARTED_AT_KEY);

        return ! is_int($startedAt) || now()->timestamp - $startedAt >= self::TIMEOUT_MINUTES * 60;
    }
}
