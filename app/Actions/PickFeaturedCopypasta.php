<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\FeaturedCopypasta;
use App\Support\CopypastaQuality;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class PickFeaturedCopypasta
{
    /** Hours of the first window, and days of the wider one used when the first has nothing. */
    public const FIRST_WINDOW_HOURS = 48;

    public const SECOND_WINDOW_DAYS = 7;

    /**
     * Picks today's copy-pasta of the day: the best by quality and freshness among the visible, non-NSFW ones
     * published in the last 48 hours that were never featured before; if there is none, among those of the last 7
     * days; if there is none either, there is no copy-pasta of the day. Running it again the same day changes
     * nothing. Returns the row of the day, if any.
     */
    public function handle(): ?FeaturedCopypasta
    {
        $today = now()->toDateString();

        $existing = FeaturedCopypasta::query()->where('date', $today)->first();

        if ($existing !== null) {
            return $existing;
        }

        $pick = $this->best(now()->subHours(self::FIRST_WINDOW_HOURS)) ?? $this->best(now()->subDays(self::SECOND_WINDOW_DAYS));

        if ($pick === null) {
            GetFeaturedCopypasta::forget();

            return null;
        }

        DB::table('featured_copypastas')->insertOrIgnore([
            'date' => $today,
            'copypasta_id' => $pick->getKey(),
            'picked_by_id' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        GetFeaturedCopypasta::forget();

        return FeaturedCopypasta::query()->where('date', $today)->first();
    }

    private function best(CarbonInterface $since): ?Copypasta
    {
        /** @var Builder<Copypasta> $query */
        $query = Copypasta::query()
            ->visible()
            ->notOnlyInactiveTags()
            ->where('is_nsfw', false)
            ->where('published_at', '>=', $since)
            ->whereNotExists(fn ($featured) => $featured->selectRaw('1')->from('featured_copypastas')
                ->whereColumn('featured_copypastas.copypasta_id', 'copypastas.id'));

        return CopypastaQuality::orderBest($query)->first();
    }
}
