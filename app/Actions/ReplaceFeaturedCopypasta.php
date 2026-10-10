<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReplaceFeaturedCopypasta
{
    /**
     * Staff replace today's copy-pasta of the day with another one (or set it when the day has none). The replaced one
     * leaves featured_copypastas, so it can be picked again some day; one that was already featured on another day
     * cannot be chosen. The change is logged.
     */
    public function handle(User $actor, Copypasta $copypasta): void
    {
        Gate::forUser($actor)->authorize('feature', $copypasta);

        $today = now()->toDateString();

        DB::transaction(function () use ($actor, $copypasta, $today): void {
            $previous = DB::table('featured_copypastas')->where('date', $today)->first();

            if ($previous?->copypasta_id === $copypasta->getKey()) {
                return;
            }

            throw_if(
                DB::table('featured_copypastas')->where('copypasta_id', $copypasta->getKey())->exists(),
                ValidationException::withMessages(['copypasta' => __('admin.featured.already_featured')]),
            );

            DB::table('featured_copypastas')->where('date', $today)->delete();

            DB::table('featured_copypastas')->insert([
                'date' => $today,
                'copypasta_id' => $copypasta->getKey(),
                'picked_by_id' => $actor->getKey(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => ModerationActionType::ReplaceFeatured,
                'subject_type' => $copypasta::class,
                'subject_id' => $copypasta->getKey(),
                'meta' => ['previous' => $previous?->copypasta_id],
            ]);
        });

        GetFeaturedCopypasta::forget();
    }
}
