<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Reads a member's affinity with tags. The stored score is as of its updated_at; reading applies the decay since then
 * and adds the fixed bonus of the favorite tags, which are stored apart and never decay.
 */
class TagAffinities
{
    public static function decayFactor(CarbonInterface $from, CarbonInterface $to): float
    {
        $days = max(0, $from->diffInSeconds($to, absolute: false)) / 86400;

        return 0.5 ** ($days / (float) config('affinity.half_life_days'));
    }

    /**
     * The effective affinity of the member with every tag they have a score or a favorite for, by tag id.
     *
     * @return array<int, float>
     */
    public function effective(User|int $user): array
    {
        $userId = $user instanceof User ? $user->getKey() : $user;
        $now = now();

        $effective = [];

        foreach (DB::table('user_tag_affinities')->where('user_id', $userId)->get(['tag_id', 'score', 'updated_at']) as $row) {
            $effective[(int) $row->tag_id] = (float) $row->score
                * self::decayFactor(Carbon::parse($row->updated_at), $now);
        }

        foreach ($this->favoriteTagIds($userId) as $tagId) {
            $effective[$tagId] = ($effective[$tagId] ?? 0.0) + (float) config('affinity.favorite_bonus');
        }

        return $effective;
    }

    /**
     * @return list<int>
     */
    public function favoriteTagIds(User|int $user): array
    {
        return DB::table('user_favorite_tags')
            ->where('user_id', $user instanceof User ? $user->getKey() : $user)
            ->pluck('tag_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();
    }

    /**
     * Whether "Para ti" is the member's default tab: they chose favorite tags or have at least five signals (votes,
     * first copies, favorites and dismissals). The signals are counted with a bounded read of the four tables.
     */
    public function defaultsToForYou(User|int $user): bool
    {
        $userId = $user instanceof User ? $user->getKey() : $user;
        $needed = (int) config('affinity.feed.default_signals');

        $rows = DB::select(
            'SELECT kind, count(*) AS total FROM ('
            ."(SELECT 'favorite' AS kind FROM user_favorite_tags WHERE user_id = ? LIMIT 1) UNION ALL "
            ."(SELECT 'signal' FROM votes WHERE user_id = ? LIMIT ?) UNION ALL "
            ."(SELECT 'signal' FROM user_copied_copypastas WHERE user_id = ? LIMIT ?) UNION ALL "
            ."(SELECT 'signal' FROM copypasta_dismissals WHERE user_id = ? LIMIT ?) UNION ALL "
            ."(SELECT 'signal' FROM copypasta_folder JOIN folders ON folders.id = copypasta_folder.folder_id WHERE folders.user_id = ? AND folders.is_default = true LIMIT ?)"
            .') found GROUP BY kind',
            [$userId, $userId, $needed, $userId, $needed, $userId, $needed, $userId, $needed],
        );

        $totals = collect($rows)->pluck('total', 'kind');

        return (int) ($totals['favorite'] ?? 0) > 0 || (int) ($totals['signal'] ?? 0) >= $needed;
    }

    /**
     * The tags with the highest positive effective affinity, best first.
     *
     * @param  array<int, float>  $effective
     * @return list<int>
     */
    public function top(array $effective, int $limit): array
    {
        $positive = array_filter($effective, fn (float $score): bool => $score > 0);

        arsort($positive);

        return array_map('intval', array_slice(array_keys($positive), 0, $limit));
    }

    /**
     * The tags whose effective affinity is below the exclusion floor.
     *
     * @param  array<int, float>  $effective
     * @return list<int>
     */
    public function excluded(array $effective): array
    {
        return array_map('intval', array_keys(array_filter(
            $effective,
            fn (float $score): bool => $score < (float) config('affinity.exclusion_floor'),
        )));
    }
}
