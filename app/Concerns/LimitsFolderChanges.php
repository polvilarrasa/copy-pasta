<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

trait LimitsFolderChanges
{
    public const MAX_FOLDER_CHANGES_PER_MINUTE = 60;

    /**
     * Counts one change to the member's folders. Each membership change counts on its own, so a sync that moves a
     * copy-pasta between three folders uses three of the sixty.
     *
     * @throws ThrottleRequestsException
     */
    protected function ensureFolderChangeIsAllowed(User $user): void
    {
        throw_unless(
            RateLimiter::attempt(
                'folder-changes:'.$user->getKey(),
                self::MAX_FOLDER_CHANGES_PER_MINUTE,
                fn (): bool => true,
                60,
            ),
            new ThrottleRequestsException(__('app.errors.folder_rate_limited')),
        );
    }
}
