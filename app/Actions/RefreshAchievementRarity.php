<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Models\User;
use App\Support\AchievementRarity;
use Illuminate\Support\Facades\DB;

class RefreshAchievementRarity
{
    public function __construct(private AchievementRarity $rarity) {}

    /**
     * Stores, for every achievement, the percentage of active accounts (not banned, not anonymized, not deleted) that
     * hold it. Revoked achievements do not count. Returns the stored percentages.
     *
     * @return array<string, float>
     */
    public function handle(): array
    {
        $active = User::query()->whereNull('banned_at')->whereNull('anonymized_at')->count();

        $holders = DB::table('user_achievements')
            ->join('users', 'users.id', '=', 'user_achievements.user_id')
            ->whereNull('user_achievements.revoked_at')
            ->whereNull('users.banned_at')
            ->whereNull('users.anonymized_at')
            ->whereNull('users.deleted_at')
            ->groupBy('user_achievements.achievement_key')
            ->selectRaw('user_achievements.achievement_key as achievement_key, count(*) as total')
            ->pluck('total', 'achievement_key');

        $percentages = [];

        foreach (Achievement::cases() as $achievement) {
            $percentages[$achievement->value] = $active === 0
                ? 0.0
                : round(((int) ($holders[$achievement->value] ?? 0)) / $active * 100, 2);
        }

        $this->rarity->store($percentages);

        return $percentages;
    }
}
