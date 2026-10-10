<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\Achievement;
use Illuminate\Support\Facades\Cache;

/**
 * The share of active accounts (not banned, not anonymized) that hold each achievement, as computed once a day by
 * App\Actions\RefreshAchievementRarity and kept in the cache.
 */
class AchievementRarity
{
    public const CACHE_KEY = 'achievements.rarity';

    /**
     * @param  array<string, float>  $percentages  achievement key => percentage of active accounts
     */
    public function store(array $percentages): void
    {
        Cache::forever(self::CACHE_KEY, $percentages);
    }

    /**
     * @return array<string, float>
     */
    public function all(): array
    {
        /** @var array<string, float> $stored */
        $stored = Cache::get(self::CACHE_KEY, []);

        return $stored;
    }

    /**
     * "12 %", or "<1 %" below one percent. Null while the daily job has not run yet.
     *
     * @param  array<string, float>|null  $percentages  the result of all(), to read the cache once for a whole list
     */
    public function label(Achievement $achievement, ?array $percentages = null): ?string
    {
        $percentages ??= $this->all();

        if (! array_key_exists($achievement->value, $percentages)) {
            return null;
        }

        $percentage = $percentages[$achievement->value];

        return $percentage < 1
            ? __('achievements.rarity.less_than_one')
            : __('achievements.rarity.percent', ['value' => number_format((float) round($percentage), 0, ',', '.')]);
    }
}
