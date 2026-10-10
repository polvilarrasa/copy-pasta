<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\AffinitySignal;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UndoDismissCopypasta
{
    public function __construct(private RecordEvent $recordEvent, private AdjustTagAffinity $adjustAffinity) {}

    /**
     * "Deshacer": the copy-pasta can appear again and the affinity gets back the points the dismissal took. Undoing
     * what was not dismissed changes nothing. Returns whether this call restored it.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(User $user, Copypasta $copypasta, array $context = []): bool
    {
        $restored = DB::transaction(function () use ($user, $copypasta): bool {
            $deleted = DB::table('copypasta_dismissals')
                ->where('user_id', $user->getKey())
                ->where('copypasta_id', $copypasta->getKey())
                ->delete() === 1;

            if ($deleted) {
                $this->adjustAffinity->handle($user, $copypasta, -AffinitySignal::Dismiss->weight());
            }

            return $deleted;
        });

        if ($restored) {
            $this->recordEvent->handle(EventType::DismissUndo, $user, $copypasta, $context);
        }

        return $restored;
    }
}
