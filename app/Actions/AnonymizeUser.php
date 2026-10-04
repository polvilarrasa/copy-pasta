<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\ModerationAction;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnonymizeUser
{
    /**
     * Retires the member's votes and favorites from other people's counters, deletes their folders, and frees the
     * username and email by replacing them. Credentials and two-factor data are cleared and the account is
     * soft-deleted, so it cannot sign in. Copy-pastas and reports stay in place, attributed to the anonymized account.
     * The actor is null for the automatic purge.
     */
    public function handle(User $user, ?User $actor): void
    {
        DB::transaction(function () use ($user, $actor): void {
            $this->retireVotes($user);
            $this->retireFavorites($user);

            $user->folders()->delete();
            $user->passkeys()->delete();

            $user->forceFill([
                'username' => 'eliminado-'.$user->getKey(),
                'email' => 'eliminado-'.$user->getKey().'@anonimo.invalid',
                'password' => Str::random(64),
                'role' => Role::User,
                'show_nsfw' => false,
                'must_change_password' => false,
                'email_verified_at' => null,
                'two_factor_secret' => null,
                'two_factor_recovery_codes' => null,
                'two_factor_confirmed_at' => null,
                'remember_token' => null,
                'anonymized_at' => now(),
            ])->save();

            if (! $user->trashed()) {
                $user->delete();
            }

            ModerationAction::query()->create([
                'actor_id' => $actor?->getKey(),
                'action' => ModerationActionType::UserAnonymized,
                'subject_type' => $user::class,
                'subject_id' => $user->getKey(),
            ]);
        });
    }

    /**
     * Decrements the vote counters with atomic deltas and recomputes the score of the copy-pastas affected.
     */
    private function retireVotes(User $user): void
    {
        $upvotedIds = Vote::query()->where('user_id', $user->getKey())->where('value', 1)->pluck('copypasta_id');
        $downvotedIds = Vote::query()->where('user_id', $user->getKey())->where('value', -1)->pluck('copypasta_id');

        if ($upvotedIds->isNotEmpty()) {
            Copypasta::query()->whereKey($upvotedIds)->decrement('upvotes_count');
        }

        if ($downvotedIds->isNotEmpty()) {
            Copypasta::query()->whereKey($downvotedIds)->decrement('downvotes_count');
        }

        Vote::query()->where('user_id', $user->getKey())->delete();

        $affectedIds = $upvotedIds->merge($downvotedIds)->unique();

        if ($affectedIds->isNotEmpty()) {
            Copypasta::query()
                ->whereKey($affectedIds)
                ->update(['score' => DB::raw('upvotes_count - downvotes_count')]);
        }
    }

    /**
     * Only the default folder counts as favorites, so only its entries move the favorites counter.
     */
    private function retireFavorites(User $user): void
    {
        $defaultFolder = Folder::query()->where('user_id', $user->getKey())->where('is_default', true)->first();

        if ($defaultFolder === null) {
            return;
        }

        $favoriteIds = $defaultFolder->copypastas()->pluck('copypastas.id');

        if ($favoriteIds->isNotEmpty()) {
            Copypasta::query()->whereKey($favoriteIds)->decrement('favorites_count');
        }
    }
}
