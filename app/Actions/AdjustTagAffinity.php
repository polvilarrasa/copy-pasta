<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdjustTagAffinity
{
    /**
     * Adds the delta to the member's affinity with every tag of the copy-pasta, with one atomic upsert. The stored
     * score decays lazily: new = old × 0.5^(days since updated_at / half life) + delta. Call it inside the transaction
     * of the action that causes the signal, so a rollback takes the affinity with it.
     */
    public function handle(User|int $user, Copypasta|string $copypasta, float $delta): void
    {
        if ($delta === 0.0) {
            return;
        }

        DB::statement(
            'INSERT INTO user_tag_affinities (user_id, tag_id, score, updated_at) '
            .'SELECT ?, copypasta_tag.tag_id, ?, ? FROM copypasta_tag WHERE copypasta_tag.copypasta_id = ? '
            .'ON CONFLICT (user_id, tag_id) DO UPDATE SET '
            .'score = user_tag_affinities.score * power(0.5, GREATEST(0, EXTRACT(EPOCH FROM (excluded.updated_at - user_tag_affinities.updated_at))) / 86400 / ?) + excluded.score, '
            .'updated_at = excluded.updated_at',
            [
                $user instanceof User ? $user->getKey() : $user,
                $delta,
                now()->toDateTimeString(),
                $copypasta instanceof Copypasta ? $copypasta->getKey() : $copypasta,
                (float) config('affinity.half_life_days'),
            ],
        );
    }
}
