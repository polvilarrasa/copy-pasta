<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Support\TagAffinities;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RebuildAffinities
{
    private const CHUNK = 1000;

    /**
     * Recalculates every member's affinity from the data the signals leave: current votes, entries in Favorites, first
     * copies (recovered from `events` into user_copied_copypastas) and dismissals. Each contribution is dated with the
     * moment of the signal and folded in time order with the same decay the live updates apply, so the result matches
     * what the live calculation produces. Replaces the stored scores, so running it again gives the same result.
     * Favorite tags are not touched. Returns how many scores were stored.
     */
    public function handle(): int
    {
        $this->recoverFirstCopies();

        return DB::transaction(function (): int {
            DB::table('user_tag_affinities')->delete();

            $stored = 0;
            $buffer = [];
            $current = null;
            $score = 0.0;
            $at = null;

            foreach ($this->contributions() as $row) {
                $key = $row->user_id.':'.$row->tag_id;
                $when = Carbon::parse($row->at);

                if ($key !== $current) {
                    $this->flush($buffer, $current, $score, $at);
                    $current = $key;
                    $score = 0.0;
                    $at = null;
                }

                $score = ($at === null ? 0.0 : $score * TagAffinities::decayFactor($at, $when)) + (float) $row->delta;
                $at = $when;

                if (count($buffer) >= self::CHUNK) {
                    $stored += $this->insert($buffer);
                }
            }

            $this->flush($buffer, $current, $score, $at);

            return $stored + $this->insert($buffer);
        });
    }

    /**
     * Copies were only recorded as events before user_copied_copypastas existed; the first one of each member and
     * copy-pasta is what counts.
     */
    private function recoverFirstCopies(): void
    {
        DB::statement(
            'INSERT INTO user_copied_copypastas (user_id, copypasta_id, created_at) '
            .'SELECT events.user_id, events.copypasta_id, MIN(events.created_at) FROM events '
            .'JOIN copypastas ON copypastas.id = events.copypasta_id '
            .'WHERE events.type = ? AND events.user_id IS NOT NULL GROUP BY events.user_id, events.copypasta_id '
            .'ON CONFLICT (user_id, copypasta_id) DO NOTHING',
            [EventType::Copy->value],
        );
    }

    /**
     * Every signal as a (member, tag, moment, points) row, in the order they are folded.
     *
     * @return \Generator<int, object{user_id: int, tag_id: int, at: string, delta: float}>
     */
    private function contributions(): \Generator
    {
        $sql = 'SELECT signals.user_id, copypasta_tag.tag_id, signals.at, signals.delta FROM ('
            .'SELECT votes.user_id, votes.copypasta_id, votes.updated_at AS at, CASE votes.value WHEN 1 THEN ?::float ELSE ?::float END AS delta FROM votes '
            .'UNION ALL SELECT folders.user_id, copypasta_folder.copypasta_id, COALESCE(copypasta_folder.created_at, folders.updated_at), ?::float '
            .'FROM copypasta_folder JOIN folders ON folders.id = copypasta_folder.folder_id WHERE folders.is_default = true '
            .'UNION ALL SELECT user_id, copypasta_id, created_at, ?::float FROM user_copied_copypastas '
            .'UNION ALL SELECT user_id, copypasta_id, created_at, ?::float FROM copypasta_dismissals'
            .') signals JOIN copypasta_tag ON copypasta_tag.copypasta_id = signals.copypasta_id '
            .'JOIN users ON users.id = signals.user_id WHERE users.anonymized_at IS NULL '
            .'ORDER BY signals.user_id, copypasta_tag.tag_id, signals.at';

        yield from DB::cursor($sql, [
            AffinitySignal::Upvote->weight(),
            AffinitySignal::Downvote->weight(),
            AffinitySignal::Favorite->weight(),
            AffinitySignal::Copy->weight(),
            AffinitySignal::Dismiss->weight(),
        ]);
    }

    /**
     * @param  list<array<string, mixed>>  $buffer
     */
    private function flush(array &$buffer, ?string $key, float $score, ?Carbon $at): void
    {
        if ($key === null || $at === null) {
            return;
        }

        [$userId, $tagId] = explode(':', $key);

        $buffer[] = [
            'user_id' => (int) $userId,
            'tag_id' => (int) $tagId,
            'score' => $score,
            'updated_at' => $at->toDateTimeString(),
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $buffer
     */
    private function insert(array &$buffer): int
    {
        $count = count($buffer);

        if ($count > 0) {
            DB::table('user_tag_affinities')->insert($buffer);
            $buffer = [];
        }

        return $count;
    }
}
