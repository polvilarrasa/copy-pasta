<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class GetFeaturedCopypasta
{
    /**
     * Today's copy-pasta of the day, or null. Which one it is stays in the cache until midnight; the copy-pasta itself
     * is read every time and only shown while it is visible and not adult content. When the featured one is hidden,
     * deleted or marked NSFW the day has no copy-pasta until the next midnight or until staff replace it.
     */
    public function handle(?User $viewer): ?Copypasta
    {
        $id = Cache::remember(
            self::cacheKey(),
            now()->endOfDay(),
            fn (): string => (string) DB::table('featured_copypastas')->where('date', now()->toDateString())->value('copypasta_id'),
        );

        if ($id === '') {
            return null;
        }

        return Copypasta::query()
            ->visible()
            ->where('is_nsfw', false)
            ->whereKey($id)
            ->withViewerState($viewer)
            ->with(['user:id,username,title_key,anonymized_at,banned_at', 'tags:id,name,slug,color'])
            ->first();
    }

    public static function cacheKey(): string
    {
        return 'featured:'.now()->toDateString();
    }

    public static function forget(): void
    {
        Cache::forget(self::cacheKey());
    }

    /**
     * Drops today's cached pick when it is this copy-pasta. Called by the actions that hide, delete or mark as NSFW.
     */
    public static function forgetIfFeatured(Copypasta $copypasta): void
    {
        if (DB::table('featured_copypastas')->where('date', now()->toDateString())->where('copypasta_id', $copypasta->getKey())->exists()) {
            self::forget();
        }
    }
}
