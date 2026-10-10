<?php

declare(strict_types=1);

namespace App\Concerns;

use App\Models\User;
use App\Support\UnicodeText;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

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

    /**
     * A folder name goes through the same Unicode cleaning as a title. It must not end up empty: folders can be public.
     *
     * @throws ValidationException
     */
    protected function cleanName(string $name): string
    {
        $name = UnicodeText::cleanTitle($name);

        throw_if($name === '', ValidationException::withMessages(['name' => __('app.folders.errors.name_empty')]));

        return $name;
    }
}
