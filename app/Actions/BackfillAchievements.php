<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class BackfillAchievements
{
    /**
     * Distinct (visitor, link owner, day) of the retained detail views that came through a share code: the same rule
     * the live count follows. Own visits and those to copy-pastas that are not visible now do not count.
     */
    private const ATTRIBUTED_VISITS = 'SELECT DISTINCT owners.id AS owner_id, '
        ."COALESCE('u:' || events.user_id::text, events.visitor_hash) AS visitor_key, events.created_at::date AS day "
        .'FROM events JOIN users owners ON owners.share_code = events.context ->> \'ref\' '
        .'JOIN copypastas ON copypastas.id = events.copypasta_id '
        ."WHERE events.type = '".EventType::DetailView->value."' "
        .'AND copypastas.published_at IS NOT NULL AND copypastas.hidden_at IS NULL AND copypastas.deleted_at IS NULL '
        .'AND events.user_id IS DISTINCT FROM owners.id AND owners.deleted_at IS NULL '
        .'AND COALESCE(events.user_id::text, events.visitor_hash) IS NOT NULL';

    public function __construct(
        private GrantTrendingAchievements $trending,
        private AdjustAchievementProgress $adjustProgress,
        private RefreshAchievementRarity $refreshRarity,
    ) {}

    /**
     * Computes the progress of every member and the achievements it earns from the data that already exists. It never
     * sends a notification, and running it again changes nothing: counters are replaced by their recomputed value,
     * flags are only raised, and grants ignore achievements the member already has (revoked ones included).
     *
     * Copies received and Dinamita come from the events that are still retained, and En tendencia from the current
     * weekly top: those cannot be reconstructed further back. Returns how many achievements were granted.
     */
    public function handle(): int
    {
        DB::transaction(function (): void {
            $this->classifyUpvotes();
            $this->backfillCounters();
            $this->backfillFlags();
            $this->backfillDailyReferrals();
        });

        $granted = $this->grantAchievements();

        $this->refreshRarity->handle();

        return $granted;
    }

    /**
     * An upvote counted if its voter was verified and at least 72 hours old when it was cast (or last changed).
     */
    private function classifyUpvotes(): void
    {
        DB::update(
            'UPDATE votes SET counts_for_achievements = ('
            .'votes.value = 1 AND users.email_verified_at IS NOT NULL AND users.email_verified_at <= votes.updated_at '
            .'AND users.created_at <= votes.updated_at - make_interval(hours => ?)'
            .') FROM users WHERE users.id = votes.user_id',
            [User::ACHIEVEMENT_VOTE_MIN_AGE_HOURS],
        );
    }

    private function backfillCounters(): void
    {
        $queries = [
            AchievementMetric::Published->value => 'SELECT user_id, count(*) AS total FROM copypastas '
                .'WHERE published_at IS NOT NULL AND hidden_at IS NULL AND deleted_at IS NULL GROUP BY user_id',
            AchievementMetric::NightPublications->value => 'SELECT user_id, count(*) AS total FROM copypastas '
                .'WHERE published_at IS NOT NULL AND EXTRACT(HOUR FROM published_at) = 3 GROUP BY user_id',
            AchievementMetric::UpvotesReceived->value => 'SELECT copypastas.user_id AS user_id, count(*) AS total FROM votes '
                .'JOIN copypastas ON copypastas.id = votes.copypasta_id '
                .'WHERE votes.value = 1 AND votes.counts_for_achievements GROUP BY copypastas.user_id',
            AchievementMetric::CopiesReceived->value => 'SELECT copypastas.user_id AS user_id, count(*) AS total FROM events '
                .'JOIN copypastas ON copypastas.id = events.copypasta_id '
                ."WHERE events.type = '".EventType::Copy->value."' AND (events.user_id IS NULL OR events.user_id <> copypastas.user_id) "
                .'GROUP BY copypastas.user_id',
            AchievementMetric::FoldersCreated->value => 'SELECT user_id, count(*) AS total FROM folders '
                .'WHERE is_default = false GROUP BY user_id',
            AchievementMetric::Saved->value => 'SELECT folders.user_id AS user_id, count(*) AS total FROM copypasta_folder '
                .'JOIN folders ON folders.id = copypasta_folder.folder_id WHERE folders.is_default = true GROUP BY folders.user_id',
            AchievementMetric::ReportsAccepted->value => 'SELECT reporter_id AS user_id, count(*) AS total FROM reports '
                ."WHERE status = 'accepted' AND reporter_id IS NOT NULL GROUP BY reporter_id",
            AchievementMetric::VotesCast->value => 'SELECT user_id, count(*) AS total FROM votes GROUP BY user_id',
            AchievementMetric::AttributedVisits->value => 'SELECT owner_id AS user_id, count(*) AS total FROM ('.self::ATTRIBUTED_VISITS.') visits GROUP BY owner_id',
        ];

        foreach ($queries as $metric => $select) {
            DB::table('user_achievement_progress')->where('metric', $metric)->delete();

            DB::insert(
                'INSERT INTO user_achievement_progress (user_id, metric, value) SELECT totals.user_id, ?, totals.total FROM ('
                .$select.') totals WHERE totals.total > 0',
                [$metric],
            );
        }
    }

    /**
     * The per-day visits the stats page reads, rebuilt from the same retained events as the metric.
     */
    private function backfillDailyReferrals(): void
    {
        DB::table('user_daily_referrals')->delete();

        DB::insert(
            'INSERT INTO user_daily_referrals (user_id, date, visits) '
            .'SELECT owner_id, day, count(*) FROM ('.self::ATTRIBUTED_VISITS.') visits GROUP BY owner_id, day',
        );
    }

    /**
     * Flags only go up, so a rerun never lowers what a live action already raised.
     */
    private function backfillFlags(): void
    {
        foreach ($this->trending->authorIds() as $authorId) {
            $this->adjustProgress->raiseTo($authorId, AchievementMetric::TrendingTop, 1);
        }

        $window = DetectDynamiteCopypasta::WINDOW_HOURS;

        DB::insert(
            'INSERT INTO user_achievement_progress (user_id, metric, value) SELECT DISTINCT windows.author_id, ?, 1 FROM ('
            .'SELECT copypastas.user_id AS author_id, count(*) OVER ('
            .'PARTITION BY events.copypasta_id ORDER BY events.created_at '
            ."RANGE BETWEEN INTERVAL '{$window} hours' PRECEDING AND CURRENT ROW) AS copies "
            .'FROM events JOIN copypastas ON copypastas.id = events.copypasta_id '
            ."WHERE events.type = '".EventType::Copy->value."' AND copypastas.copies_count >= ? "
            .'AND (events.user_id IS NULL OR events.user_id <> copypastas.user_id)'
            .') windows WHERE windows.copies >= ? '
            .'ON CONFLICT (user_id, metric) DO UPDATE SET value = GREATEST(user_achievement_progress.value, 1)',
            [AchievementMetric::Dynamite->value, DetectDynamiteCopypasta::COPIES, DetectDynamiteCopypasta::COPIES],
        );
    }

    /**
     * Grants, without notifying, what the stored progress reaches. Only active accounts earn achievements.
     */
    private function grantAchievements(): int
    {
        $granted = 0;

        foreach (Achievement::cases() as $achievement) {
            $metric = $achievement->metric();

            $granted += $metric->isDerived()
                ? $this->grantByAccountAge($achievement)
                : DB::affectingStatement(
                    'INSERT INTO user_achievements (user_id, achievement_key, unlocked_at) '
                    .'SELECT progress.user_id, ?, ? FROM user_achievement_progress progress '
                    .'JOIN users ON users.id = progress.user_id '
                    .'WHERE progress.metric = ? AND progress.value >= ? '
                    .'AND users.banned_at IS NULL AND users.anonymized_at IS NULL AND users.deleted_at IS NULL '
                    .'ON CONFLICT (user_id, achievement_key) DO NOTHING',
                    [$achievement->value, now()->toDateTimeString(), $metric->value, $achievement->threshold()],
                );
        }

        return $granted;
    }

    private function grantByAccountAge(Achievement $achievement): int
    {
        return DB::affectingStatement(
            'INSERT INTO user_achievements (user_id, achievement_key, unlocked_at) '
            .'SELECT users.id, ?, users.created_at + make_interval(days => ?) FROM users '
            .'WHERE users.created_at <= ? '
            .'AND users.banned_at IS NULL AND users.anonymized_at IS NULL AND users.deleted_at IS NULL '
            .'ON CONFLICT (user_id, achievement_key) DO NOTHING',
            [$achievement->value, $achievement->threshold(), now()->subDays($achievement->threshold())->toDateTimeString()],
        );
    }
}
