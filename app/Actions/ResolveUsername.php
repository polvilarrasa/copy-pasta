<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\User;
use App\Models\UsernameHistory;

class ResolveUsername
{
    public const REDIRECT_DAYS = 90;

    /**
     * The username a profile link points to today. A name held by an account wins; otherwise a name left within the
     * last 90 days leads to the account that left it. Anonymized accounts and names nobody holds resolve to null.
     */
    public function handle(string $username): ?string
    {
        $holder = User::query()->where('username', $username)->first();

        if ($holder !== null) {
            return $holder->isAnonymized() ? null : $holder->username;
        }

        $entry = UsernameHistory::query()
            ->where('username', $username)
            ->where('changed_at', '>=', now()->subDays(self::REDIRECT_DAYS))
            ->latest('changed_at')
            ->first();

        $owner = $entry === null ? null : User::query()->find($entry->user_id);

        if ($owner === null || $owner->isAnonymized()) {
            return null;
        }

        return $owner->username;
    }
}
