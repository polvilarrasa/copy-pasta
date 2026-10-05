<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Support\VisitorHash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class RecordEvent
{
    public function __construct(private VisitorHash $visitorHash) {}

    /**
     * Writes one event. Anonymous events carry the daily visitor hash instead of a user. A failed write is logged and
     * swallowed: the savepoint keeps an enclosing transaction usable, and the action that caused the event still
     * succeeds.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(EventType $type, ?User $user = null, ?Copypasta $copypasta = null, array $context = []): void
    {
        try {
            DB::transaction(fn () => TrackedEvent::query()->create([
                'type' => $type,
                'user_id' => $user?->getKey(),
                'visitor_hash' => $user === null ? $this->visitorHash->forRequest(request()) : null,
                'copypasta_id' => $copypasta?->getKey(),
                'context' => $context,
                'created_at' => now(),
            ]));
        } catch (Throwable $exception) {
            Log::error('No se pudo registrar un evento', [
                'type' => $type->value,
                'copypasta_id' => $copypasta?->getKey(),
                'exception' => $exception,
            ]);
        }
    }
}
