<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Enums\EventType;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class ChangeUserTitle
{
    public const MAX_CHANGES_PER_HOUR = 20;

    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * Sets the title shown next to the member's name, or none. The title must belong to an achievement the member has
     * earned and that has not been revoked. Choosing the title already set is a no-op.
     */
    public function handle(User $user, ?string $titleKey): User
    {
        $titleKey = blank($titleKey) ? null : $titleKey;

        if ($titleKey === $user->title_key) {
            return $user;
        }

        throw_unless(
            RateLimiter::attempt('title:'.$user->getKey(), self::MAX_CHANGES_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('notifications.errors.rate_limited')),
        );

        if ($titleKey !== null) {
            throw_unless($this->isAvailable($user, $titleKey), ValidationException::withMessages([
                'title' => __('achievements.settings.invalid'),
            ]));
        }

        DB::transaction(fn () => $user->forceFill(['title_key' => $titleKey])->save());

        $this->recordEvent->handle(EventType::TitleChange, $user, context: ['title' => $titleKey]);

        return $user;
    }

    private function isAvailable(User $user, string $titleKey): bool
    {
        $achievement = Achievement::forTitleKey($titleKey);

        return $achievement !== null
            && $user->canEarnAchievements()
            && $user->achievements()
                ->where('achievement_key', $achievement->value)
                ->whereNull('revoked_at')
                ->exists();
    }
}
