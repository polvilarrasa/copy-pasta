<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Support\AchievementEntry;
use App\Support\AchievementRarity;
use App\Support\ProfileAchievements;
use Illuminate\Support\Facades\DB;

class ListProfileAchievements
{
    public function __construct(private AchievementRarity $rarity) {}

    /**
     * The achievements section of a profile, in two queries at most: the earned rows and, for the owner only, the
     * progress table. Secrets show by name only once earned, and only to their owner. Pending achievements are the
     * next tier of each family the member has not completed, so the list stays short.
     */
    public function handle(User $profile, ?User $viewer): ProfileAchievements
    {
        $isOwner = $viewer !== null && $viewer->is($profile);

        $rows = UserAchievement::query()->where('user_id', $profile->getKey())->get();

        $held = $rows->whereNull('revoked_at')->keyBy('achievement_key');
        $percentages = $this->rarity->all();

        $earned = [];
        $secretsEarned = 0;

        foreach (Achievement::cases() as $achievement) {
            $row = $held->get($achievement->value);

            if ($row === null) {
                continue;
            }

            if ($achievement->isSecret()) {
                $secretsEarned++;
            }

            if ($achievement->isSecret() && ! $isOwner) {
                continue;
            }

            $earned[] = new AchievementEntry(
                achievement: $achievement,
                unlockedAt: $row->unlocked_at,
                rarity: $this->rarity->label($achievement, $percentages),
                isActiveTitle: $achievement->titleKey() !== null && $achievement->titleKey() === $profile->title_key,
            );
        }

        usort($earned, fn (AchievementEntry $a, AchievementEntry $b): int => $b->unlockedAt?->getTimestamp() <=> $a->unlockedAt?->getTimestamp());

        if (! $isOwner) {
            return new ProfileAchievements($earned, [], 0, $secretsEarned, false);
        }

        $revoked = $rows->whereNotNull('revoked_at')->pluck('achievement_key')->all();

        return new ProfileAchievements(
            earned: $earned,
            pending: $this->pending($profile, $held->keys()->all(), $revoked),
            hiddenSecrets: count(array_filter(
                Achievement::cases(),
                fn (Achievement $achievement): bool => $achievement->isSecret()
                    && ! $held->has($achievement->value)
                    && ! in_array($achievement->value, $revoked, true),
            )),
            secretsEarned: $secretsEarned,
            isOwner: true,
        );
    }

    /**
     * @param  list<int|string>  $heldKeys
     * @param  list<string>  $revokedKeys
     * @return list<AchievementEntry>
     */
    private function pending(User $profile, array $heldKeys, array $revokedKeys): array
    {
        $stored = DB::table('user_achievement_progress')->where('user_id', $profile->getKey())->pluck('value', 'metric');

        $next = [];

        foreach (Achievement::cases() as $achievement) {
            if ($achievement->isSecret()
                || in_array($achievement->value, $heldKeys, true)
                || in_array($achievement->value, $revokedKeys, true)
                || isset($next[$achievement->family()->value])) {
                continue;
            }

            $metric = $achievement->metric();
            $value = $metric->isDerived() ? $metric->derivedValue($profile) : (int) ($stored[$metric->value] ?? 0);

            $next[$achievement->family()->value] = new AchievementEntry(
                achievement: $achievement,
                current: min($value, $achievement->threshold()),
                max: $achievement->threshold(),
            );
        }

        return array_values($next);
    }
}
