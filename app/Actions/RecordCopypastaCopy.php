<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
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

        DB::transaction(function () use ($copypasta, $isOwnCopy): void {
            Copypasta::query()->whereKey($copypasta->getKey())->increment('copies_count');

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
}
