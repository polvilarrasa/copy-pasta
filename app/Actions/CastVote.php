<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class CastVote
{
    /**
     * The vote the visitor pressed decides the outcome: the same value removes the vote,
     * the opposite value replaces it. Returns the resulting vote, or null when removed.
     */
    public function handle(User $voter, Copypasta $copypasta, int $value): ?int
    {
        Gate::forUser($voter)->authorize('vote', $copypasta);

        throw_unless(in_array($value, [1, -1], true), InvalidArgumentException::class);

        return DB::transaction(function () use ($voter, $copypasta, $value): ?int {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            $existing = Vote::query()
                ->where('user_id', $voter->getKey())
                ->where('copypasta_id', $locked->getKey())
                ->lockForUpdate()
                ->first();

            $previous = $existing?->value;
            $next = $previous === $value ? null : $value;

            if ($existing !== null && $next === null) {
                $existing->delete();
            } elseif ($existing !== null) {
                $existing->update(['value' => $next]);
            } elseif ($next !== null) {
                Vote::query()->create([
                    'user_id' => $voter->getKey(),
                    'copypasta_id' => $locked->getKey(),
                    'value' => $next,
                ]);
            }

            $locked->forceFill([
                'upvotes_count' => $locked->upvotes_count + $this->upvoteDelta($previous, $next),
                'downvotes_count' => $locked->downvotes_count + $this->downvoteDelta($previous, $next),
            ]);
            $locked->score = $locked->upvotes_count - $locked->downvotes_count;
            $locked->save();

            return $next;
        });
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
