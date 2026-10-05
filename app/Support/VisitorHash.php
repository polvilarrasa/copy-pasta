<?php

declare(strict_types=1);

namespace App\Support;

use Illuminate\Http\Request;

/**
 * Pseudonymous key for anonymous visitors, without cookies. It mixes IP and user agent with a salt that changes every
 * day and is derived from the app key, never stored, so two days of hashes cannot be linked to each other.
 */
final class VisitorHash
{
    public function forRequest(Request $request): ?string
    {
        $ip = $request->ip();

        if ($ip === null) {
            return null;
        }

        return hash_hmac('sha256', $ip.'|'.(string) $request->userAgent(), $this->dailySalt());
    }

    private function dailySalt(): string
    {
        return hash_hmac('sha256', now()->toDateString(), (string) config('app.key'));
    }
}
