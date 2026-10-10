<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Tag;
use App\Models\User;
use App\Support\ForYouCache;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class UpdateFavoriteTags
{
    public const MAX_UPDATES_PER_HOUR = 30;

    public function __construct(private RecordEvent $recordEvent, private ForYouCache $forYouCache) {}

    /**
     * Sets the member's favorite tags to the given active ones (at least three), which also marks the welcome screen as
     * done and drops their cached "Para ti" list. Editing is adding and removing rows: scores are not rebuilt.
     *
     * @param  array<int, int|string>  $tagIds
     */
    public function handle(User $user, array $tagIds): User
    {
        throw_unless(
            RateLimiter::attempt('favorite-tags:'.$user->getKey(), self::MAX_UPDATES_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('notifications.errors.rate_limited')),
        );

        $ids = Tag::query()
            ->where('is_active', true)
            ->whereIn('id', array_map('intval', $tagIds))
            ->pluck('id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        throw_if(
            count($ids) < (int) config('affinity.onboarding_min_tags'),
            ValidationException::withMessages(['tags' => __('public.welcome.min_tags', ['min' => config('affinity.onboarding_min_tags')])]),
        );

        DB::transaction(function () use ($user, $ids): void {
            DB::table('user_favorite_tags')->where('user_id', $user->getKey())->whereNotIn('tag_id', $ids)->delete();

            DB::table('user_favorite_tags')->insertOrIgnore(array_map(fn (int $tagId): array => [
                'user_id' => $user->getKey(),
                'tag_id' => $tagId,
                'created_at' => now(),
            ], $ids));

            if ($user->onboarded_at === null) {
                $user->forceFill(['onboarded_at' => now()])->save();
            }
        });

        $this->forYouCache->forgetCandidates($user);

        $this->recordEvent->handle(EventType::FavoriteTagsUpdate, $user, context: ['tag_ids' => $ids]);

        return $user;
    }
}
