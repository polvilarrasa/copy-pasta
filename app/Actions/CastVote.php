<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Enums\MilestoneMetric;
use App\Models\Copypasta;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CastVote
{
    public function __construct(
        private RecordEvent $recordEvent,
        private DetectCopypastaMilestones $detectMilestones,
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
        private AdjustTagAffinity $adjustAffinity,
    ) {}

    /**
     * The vote the visitor pressed decides the outcome: the same value removes the vote,
     * the opposite value replaces it. Returns the resulting vote, or null when removed.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $voter, Copypasta $copypasta, int $value, array $context = []): ?int
    {
        Gate::forUser($voter)->authorize('vote', $copypasta);

        throw_unless(in_array($value, [1, -1], true), InvalidArgumentException::class);

        $previous = null;

        $result = DB::transaction(function () use ($voter, $copypasta, $value, &$previous): ?int {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            $existing = Vote::query()
                ->where('user_id', $voter->getKey())
                ->where('copypasta_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            $previous = $existing?->value;
            $next = $previous === $value ? null : $value;

            $countedBefore = $existing !== null && $existing->counts_for_achievements;
            $countsNow = $next === 1 && $voter->givesAchievementUpvotes();

            if ($existing !== null && $next === null) {
                $existing->delete();
            } elseif ($existing !== null) {
                $existing->forceFill(['value' => $next, 'counts_for_achievements' => $countsNow])->save();
            } elseif ($next !== null) {
                (new Vote)->forceFill([
                    'user_id' => $voter->getKey(),
                    'copypasta_id' => $locked->getKey(),
                    'value' => $next,
                    'counts_for_achievements' => $countsNow,
                ])->save();
            }

            $this->adjustAffinity->handle($voter, $locked, AffinitySignal::voteWeight($next) - AffinitySignal::voteWeight($previous));

            $this->recordAchievementProgress($voter, $locked->user_id, $existing !== null, $next, $countedBefore, $countsNow);

            $locked->forceFill([
                'upvotes_count' => $locked->upvotes_count + $this->upvoteDelta($previous, $next),
                'downvotes_count' => $locked->downvotes_count + $this->downvoteDelta($previous, $next),
            ]);
            $locked->score = $locked->upvotes_count - $locked->downvotes_count;
            $locked->save();

            return $next;
        });

        $this->queueEvaluation->handle($voter, [AchievementMetric::VotesCast]);
        $this->queueEvaluation->handle($copypasta->user_id, [AchievementMetric::UpvotesReceived]);

        if ($result === 1) {
            DB::afterCommit(fn () => $this->detectMilestones->handle($copypasta, MilestoneMetric::Upvotes));
        }

        $this->recordEvent->handle(match ($result) {
            1 => EventType::VoteUp,
            -1 => EventType::VoteDown,
            null => EventType::VoteRemoved,
        }, $voter, $copypasta, [...$context, 'previous' => $previous, 'next' => $result]);

        return $result;
    }

    /**
     * The voter's active votes move when a vote is created or withdrawn; the author's received upvotes move by the
     * upvotes that counted: one that did not count when cast (young or unverified voter) is never taken back.
     */
    private function recordAchievementProgress(User $voter, int $authorId, bool $hadVote, ?int $next, bool $countedBefore, bool $countsNow): void
    {
        $this->adjustProgress->add($voter, AchievementMetric::VotesCast, ($next !== null ? 1 : 0) - ($hadVote ? 1 : 0));
        $this->adjustProgress->add($authorId, AchievementMetric::UpvotesReceived, ($countsNow ? 1 : 0) - ($countedBefore ? 1 : 0));
    }

    private function upvoteDelta(?int $previous, ?int $next): int
    {
        return ($next === 1 ? 1 : 0) - ($previous === 1 ? 1 : 0);
    }

    private function downvoteDelta(?int $previous, ?int $next): int
    {
        return ($next === -1 ? 1 : 0) - ($previous === -1 ? 1 : 0);
    }
}
