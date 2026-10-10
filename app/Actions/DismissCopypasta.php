<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class DismissCopypasta
{
    public function __construct(private RecordEvent $recordEvent, private AdjustTagAffinity $adjustAffinity) {}

    /**
     * "No me interesa": the copy-pasta stops appearing in the member's feeds and the affinity with its tags drops.
     * Dismissing it again changes nothing. Returns whether this call dismissed it.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $user, Copypasta $copypasta, array $context = []): bool
    {
        Gate::forUser($user)->authorize('dismiss', $copypasta);

        $dismissed = DB::transaction(function () use ($user, $copypasta): bool {
            $inserted = DB::table('copypasta_dismissals')->insertOrIgnore([
                'user_id' => $user->getKey(),
                'copypasta_id' => $copypasta->getKey(),
                'created_at' => now(),
            ]) === 1;

            if ($inserted) {
                $this->adjustAffinity->handle($user, $copypasta, AffinitySignal::Dismiss->weight());
            }

            return $inserted;
        });

        if ($dismissed) {
            $this->recordEvent->handle(EventType::Dismiss, $user, $copypasta, $context);
        }

        return $dismissed;
    }
}
