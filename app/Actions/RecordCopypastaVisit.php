<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\VisitorHash;
use Illuminate\Support\Facades\DB;

class RecordCopypastaVisit
{
    public function __construct(
        private RecordEvent $recordEvent,
        private VisitorHash $visitorHash,
        private AdjustAchievementProgress $adjustProgress,
        private QueueAchievementEvaluation $queueEvaluation,
    ) {}

    /**
     * Records the detail view and, when the visit came through a shared link, attributes it to the link's owner. The
     * event and the attribution share one transaction, so the counters never get ahead of the events.
     *
     * An attributed visit counts once per visitor, link owner and day, and never when the visitor is the owner, when
     * the copy-pasta is not visible to the public, or when the code belongs to nobody. Like every other metric, it keeps counting for a banned owner. Bots
     * never reach this action. Returns whether the visit was attributed.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(Copypasta $copypasta, ?User $viewer, array $context): bool
    {
        $owner = $this->owner($copypasta, $context['ref'] ?? null);

        $attributed = DB::transaction(function () use ($copypasta, $viewer, $context, $owner): bool {
            $this->recordEvent->handle(EventType::DetailView, $viewer, $copypasta, $context);

            if ($owner === null || $viewer?->is($owner)) {
                return false;
            }

            $visitorKey = $viewer !== null ? 'u:'.$viewer->getKey() : $this->visitorHash->forRequest(request());

            if ($visitorKey === null) {
                return false;
            }

            $today = now()->toDateString();

            $inserted = DB::table('attributed_visits')->insertOrIgnore([
                'visitor_key' => $visitorKey,
                'owner_id' => $owner->getKey(),
                'visited_on' => $today,
            ]);

            if ($inserted === 0) {
                return false;
            }

            DB::statement(
                'INSERT INTO user_daily_referrals (user_id, date, visits) VALUES (?, ?, 1) '
                .'ON CONFLICT (user_id, date) DO UPDATE SET visits = user_daily_referrals.visits + 1',
                [$owner->getKey(), $today],
            );

            $this->adjustProgress->add($owner, AchievementMetric::AttributedVisits, 1);

            return true;
        });

        if ($attributed && $owner !== null) {
            $this->queueEvaluation->handle($owner, [AchievementMetric::AttributedVisits]);
        }

        return $attributed;
    }

    private function owner(Copypasta $copypasta, mixed $ref): ?User
    {
        if (! is_string($ref) || $ref === '' || $copypasta->published_at === null || $copypasta->isHidden()) {
            return null;
        }

        return User::query()->where('share_code', $ref)->first();
    }
}
