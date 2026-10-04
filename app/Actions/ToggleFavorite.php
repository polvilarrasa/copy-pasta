<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class ToggleFavorite
{
    /**
     * Adds or removes the copy-pasta from the user's default folder. Returns whether it is now a favorite.
     */
    public function handle(User $user, Copypasta $copypasta): bool
    {
        Gate::forUser($user)->authorize('favorite', $copypasta);

        return DB::transaction(function () use ($user, $copypasta): bool {
            $locked = Copypasta::query()->whereKey($copypasta->getKey())->lockForUpdate()->firstOrFail();

            $favorites = Folder::query()->firstOrCreate(
                ['user_id' => $user->getKey(), 'is_default' => true],
                ['name' => Folder::DEFAULT_NAME, 'position' => 0],
            );

            $isFavorite = $favorites->copypastas()->whereKey($locked->getKey())->exists();

            if ($isFavorite) {
                $favorites->copypastas()->detach($locked->getKey());
            } else {
                $favorites->copypastas()->attach($locked->getKey(), ['created_at' => now()]);
            }

            $locked->forceFill(['favorites_count' => max(0, $locked->favorites_count + ($isFavorite ? -1 : 1))])->save();

            return ! $isFavorite;
        });
    }
}
