<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\FeedSort;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\CopypastaQuality;
use App\Support\ForYouCache;
use App\Support\TagAffinities;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;

class BuildForYouCandidates
{
    /**
     * Where a slot looks first and then, if that group has nothing left, in which order it borrows from the others.
     *
     * @var array<string, list<string>>
     */
    private const FILL_ORDER = [
        'affinity' => ['affinity', 'explore', 'recent'],
        'explore' => ['explore', 'affinity', 'recent'],
        'recent' => ['recent', 'affinity', 'explore'],
    ];

    public function __construct(private TagAffinities $affinities, private ForYouCache $cache) {}

    /**
     * The member's list of candidates, ordered as it will be shown, from the cache when it is still fresh (30 minutes).
     *
     * @return list<array{id: string, group: string}>
     */
    public function candidates(User $user, bool $refresh = false): array
    {
        $key = $this->cache->candidatesKey($user);

        if ($refresh) {
            Cache::forget($key);
        }

        /** @var list<array{id: string, group: string}>|null $cached */
        $cached = Cache::get($key);

        if ($cached !== null) {
            return $cached;
        }

        $candidates = $this->build($user);

        if ($candidates !== []) {
            Cache::put($key, $candidates, now()->addMinutes((int) config('affinity.feed.cache_minutes')));
        }

        return $candidates;
    }

    /**
     * Builds 200 candidates in three groups, each ordered by quality and freshness: copy-pastas of the five tags the
     * member likes most (14 of every 20), exploration among the rest (4) and recent ones with few votes (2). The groups
     * are placed in fixed slots of each page of 20. A group that runs dry borrows from the others and, in the end, from
     * the weekly top; when nothing is left at all, it shows the best visible content again, so it is never empty.
     *
     * @return list<array{id: string, group: string}>
     */
    public function build(User $user): array
    {
        $effective = $this->affinities->effective($user);
        $top = $this->affinities->top($effective, (int) config('affinity.feed.top_tags'));
        $excludedTags = $this->affinities->excluded($effective);
        $seen = $this->cache->seen($user);
        $size = (int) config('affinity.feed.candidates');

        $base = fn (): Builder => $this->base($user, $excludedTags, $seen);

        $isRecent = fn (Builder $query): Builder => $query
            ->where('published_at', '>=', now()->subHours((int) config('affinity.feed.recent_hours')))
            ->whereRaw('(upvotes_count + downvotes_count) <= ?', [(int) config('affinity.feed.few_votes')]);
        $isTop = fn ($tags) => $this->tagged($tags, $top);

        // The groups do not overlap: a copy-pasta of the member's favorite tags is "affinity", a fresh one with few
        // votes outside them is "recent", and anything else is "explore".
        /** @var array<string, list<string>> $pools */
        $pools = [
            'affinity' => $top === [] ? [] : $this->ids($base()->whereExists($isTop), $size),
            'explore' => $this->ids(
                $base()
                    ->when($top !== [], fn (Builder $query) => $query->whereNotExists($isTop))
                    ->whereNot(fn (Builder $query) => $isRecent($query)),
                $size,
            ),
            'recent' => $this->ids(
                $isRecent($base())->when($top !== [], fn (Builder $query) => $query->whereNotExists($isTop)),
                $size,
            ),
        ];

        $weekly = null;
        $weeklyPool = function () use (&$weekly, $base, $size): array {
            return $weekly ??= $this->ids($base()->sort(FeedSort::TopWeek), $size, byQuality: false);
        };

        $taken = [];
        $result = [];
        $pageSize = (int) config('affinity.feed.page_size');

        while (count($result) < $size) {
            $item = $this->take($this->slotGroup(count($result) % $pageSize), $pools, $weeklyPool, $taken);

            if ($item === null) {
                break;
            }

            $taken[$item['id']] = true;
            $result[] = $item;
        }

        if ($result === []) {
            foreach ($this->lastResort($user) as $id) {
                $result[] = ['id' => $id, 'group' => 'fallback'];
            }
        }

        return $result;
    }

    /**
     * @param  array<string, list<string>>  $pools
     * @param  array<string, true>  $taken
     * @return array{id: string, group: string}|null
     */
    private function take(string $group, array &$pools, callable $weeklyPool, array $taken): ?array
    {
        foreach (self::FILL_ORDER[$group] as $source) {
            while ($pools[$source] !== []) {
                $id = array_shift($pools[$source]);

                if (! isset($taken[$id])) {
                    return ['id' => $id, 'group' => $source];
                }
            }
        }

        foreach ($weeklyPool() as $id) {
            if (! isset($taken[$id])) {
                return ['id' => $id, 'group' => 'fallback'];
            }
        }

        return null;
    }

    private function slotGroup(int $slot): string
    {
        return match (true) {
            in_array($slot, config('affinity.feed.explore_slots'), true) => 'explore',
            in_array($slot, config('affinity.feed.recent_slots'), true) => 'recent',
            default => 'affinity',
        };
    }

    /**
     * Copy-pastas the member could see: visible, safe for them, not their own, not voted on, copied or dismissed, not
     * among the last ones shown to them, and without any tag they dislike (effective affinity below the floor).
     *
     * @param  list<int>  $excludedTags
     * @param  list<string>  $seen
     * @return Builder<Copypasta>
     */
    private function base(User $user, array $excludedTags, array $seen): Builder
    {
        return Copypasta::query()
            ->visible()
            ->notOnlyInactiveTags()
            ->nsfw($user->canSeeNsfw())
            ->where('copypastas.user_id', '!=', $user->getKey())
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('votes')
                ->whereColumn('votes.copypasta_id', 'copypastas.id')->where('votes.user_id', $user->getKey()))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('user_copied_copypastas')
                ->whereColumn('user_copied_copypastas.copypasta_id', 'copypastas.id')->where('user_copied_copypastas.user_id', $user->getKey()))
            ->whereNotExists(fn ($query) => $query->selectRaw('1')->from('copypasta_dismissals')
                ->whereColumn('copypasta_dismissals.copypasta_id', 'copypastas.id')->where('copypasta_dismissals.user_id', $user->getKey()))
            ->when($seen !== [], fn (Builder $query) => $query->whereNotIn('copypastas.id', $seen))
            ->when($excludedTags !== [], fn (Builder $query) => $query->whereNotExists(fn ($tags) => $this->tagged($tags, $excludedTags)));
    }

    /**
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  list<int>  $tagIds
     */
    private function tagged($query, array $tagIds): void
    {
        $query->selectRaw('1')->from('copypasta_tag')
            ->whereColumn('copypasta_tag.copypasta_id', 'copypastas.id')
            ->whereIn('copypasta_tag.tag_id', $tagIds);
    }

    /**
     * The ids of the query, best quality first unless the query keeps the order it came with (the weekly top).
     *
     * @param  Builder<Copypasta>  $query
     * @return list<string>
     */
    private function ids(Builder $query, int $limit, bool $byQuality = true): array
    {
        $query = $byQuality ? CopypastaQuality::orderBest((clone $query)->reorder()) : clone $query;

        /** @var list<string> $ids */
        $ids = $query->limit($limit)->pluck('copypastas.id')->all();

        return $ids;
    }

    /**
     * What is left when everything personal has been excluded: any visible, suitable copy-pasta that is not the
     * member's own, best of the week first, then the best overall.
     *
     * @return list<string>
     */
    private function lastResort(User $user): array
    {
        $query = fn (): Builder => Copypasta::query()
            ->visible()
            ->notOnlyInactiveTags()
            ->nsfw($user->canSeeNsfw())
            ->where('copypastas.user_id', '!=', $user->getKey());

        /** @var list<string> $ids */
        $ids = $query()->sort(FeedSort::TopWeek)
            ->limit((int) config('affinity.feed.page_size'))->pluck('copypastas.id')->all();

        if (count($ids) < (int) config('affinity.feed.page_size')) {
            $ids = array_values(array_unique([
                ...$ids,
                ...$query()->sort(FeedSort::TopAll)->limit((int) config('affinity.feed.page_size'))->pluck('copypastas.id')->all(),
            ]));
        }

        return $ids;
    }
}
