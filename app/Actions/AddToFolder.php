<?php

declare(strict_types=1);

namespace App\Actions;

use App\Concerns\LimitsFolderChanges;
use App\Enums\AchievementMetric;
use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class AddToFolder
{
    use LimitsFolderChanges;

    public function __construct(
        private RecordEvent $recordEvent,
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
        private AdjustTagAffinity $adjustAffinity,
    ) {}

    /**
     * Adds the copy-pasta to the folder. Entries in the default folder are favorites, so they also move the counter.
     * Returns false when the copy-pasta was already in the folder.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $user, Folder $folder, Copypasta $copypasta, array $context = []): bool
    {
        Gate::forUser($user)->authorize('addCopypasta', [$folder, $copypasta]);
        $this->ensureFolderChangeIsAllowed($user);

        $added = DB::transaction(function () use ($user, $folder, $copypasta): bool {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            if ($folder->copypastas()->whereKey($locked->getKey())->exists()) {
                return false;
            }

            $folder->copypastas()->attach($locked->getKey(), ['created_at' => now()]);

            if ($folder->is_default) {
                $locked->forceFill(['favorites_count' => $locked->favorites_count + 1])->save();

                $this->adjustProgress->add($user, AchievementMetric::Saved, 1);
                $this->adjustAffinity->handle($user, $locked, AffinitySignal::Favorite->weight());
            }

            return true;
        });

        if ($added) {
            if ($folder->is_default) {
                $this->queueEvaluation->handle($user, [AchievementMetric::Saved]);
            }

            $this->recordEvent->handle(
                $folder->is_default ? EventType::FavoriteAdd : EventType::FolderAdd,
                $user,
                $copypasta,
                $context,
            );
        }

        return $added;
    }
}
