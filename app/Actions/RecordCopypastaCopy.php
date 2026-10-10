<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Enums\MilestoneMetric;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RecordCopypastaCopy
{
    public function __construct(
        private RecordEvent $recordEvent,
        private DetectCopypastaMilestones $detectMilestones,
        private AdjustAchievementProgress $adjustProgress,
        private DetectDynamiteCopypasta $detectDynamite,
        private QueueAchievementEvaluation $queueEvaluation,
        private AdjustTagAffinity $adjustAffinity,
    ) {}

    /**
     * Count a copy at most once per copy-pasta, visitor and hour. Returns whether the counter moved; only a counted
     * copy is recorded as an event, so the events match the counter.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(Copypasta $copypasta, string $visitorKey, ?User $user = null, array $context = []): bool
    {
        $bucket = now()->format('YmdH');
        $cacheKey = sprintf('copypasta-copy:%s:%s:%s', $copypasta->getKey(), hash('sha256', $visitorKey), $bucket);

        if (! Cache::add($cacheKey, true, now()->addHour())) {
            return false;
        }

        $isOwnCopy = $user !== null && $user->getKey() === $copypasta->user_id;

        DB::transaction(function () use ($copypasta, $isOwnCopy, $user): void {
            Copypasta::query()->whereKey($copypasta->getKey())->increment('copies_count');

            if ($user !== null) {
                $this->recordFirstCopy($user, $copypasta);
            }

            if (! $isOwnCopy) {
                $this->adjustProgress->add($copypasta->user_id, AchievementMetric::CopiesReceived, 1);
            }
        });

        DB::afterCommit(fn () => $this->detectMilestones->handle($copypasta, MilestoneMetric::Copies));

        $this->recordEvent->handle(EventType::Copy, $user, $copypasta, $context);

        if (! $isOwnCopy) {
            $this->detectDynamite->handle($copypasta);
            $this->queueEvaluation->handle($copypasta->user_id, [AchievementMetric::CopiesReceived, AchievementMetric::Dynamite]);
        }

        return true;
    }

    /**
     * Only the first copy of each copy-pasta by each member is a signal for their affinity: the primary key of the
     * table decides it, so repeated copies add nothing.
     */
    private function recordFirstCopy(User $user, Copypasta $copypasta): void
    {
        $isFirst = DB::table('user_copied_copypastas')->insertOrIgnore([
            'user_id' => $user->getKey(),
            'copypasta_id' => $copypasta->getKey(),
            'created_at' => now(),
        ]) === 1;

        if ($isFirst) {
            $this->adjustAffinity->handle($user, $copypasta, AffinitySignal::Copy->weight());
        }
    }
}
