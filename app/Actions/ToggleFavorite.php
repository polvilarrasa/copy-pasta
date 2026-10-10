<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ToggleFavorite
{
    public function __construct(
        private RecordEvent $recordEvent,
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
        private AdjustTagAffinity $adjustAffinity,
    ) {}

    /**
     * Adds or removes the copy-pasta from the user's default folder. Returns whether it is now a favorite.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $user, Copypasta $copypasta, array $context = []): bool
    {
        Gate::forUser($user)->authorize('favorite', $copypasta);

        $isNowFavorite = DB::transaction(function () use ($user, $copypasta): bool {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            $favorites = Folder::ensureDefaultFor($user);

            $isFavorite = $favorites->copypastas()->whereKey($locked->getKey())->exists();

            if ($isFavorite) {
                $favorites->copypastas()->detach($locked->getKey());
            } else {
                $favorites->copypastas()->attach($locked->getKey(), ['created_at' => now()]);
            }

            $locked->forceFill(['favorites_count' => max(0, $locked->favorites_count + ($isFavorite ? -1 : 1))])->save();

            $this->adjustProgress->add($user, AchievementMetric::Saved, $isFavorite ? -1 : 1);
            $this->adjustAffinity->handle($user, $locked, $isFavorite ? -AffinitySignal::Favorite->weight() : AffinitySignal::Favorite->weight());

            return ! $isFavorite;
        });

        $this->queueEvaluation->handle($user, [AchievementMetric::Saved]);

        $this->recordEvent->handle(
            $isNowFavorite ? EventType::FavoriteAdd : EventType::FavoriteRemove,
            $user,
            $copypasta,
            $context,
        );

        return $isNowFavorite;
    }
}
