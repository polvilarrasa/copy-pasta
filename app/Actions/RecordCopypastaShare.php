<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;

class RecordCopypastaShare
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * Records a share and returns the reference code its link carries: the sharer's own `share_code`, or null when
     * they have no session, because anonymous visitors share without a ref. The share event notes the ref only when
     * there is one, and the way it was shared (`link` or `image`) in `method`.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(Copypasta $copypasta, ?User $user, array $context = []): ?string
    {
        $reference = $user?->share_code;

        $this->recordEvent->handle(
            EventType::Share,
            $user,
            $copypasta,
            array_filter([...$context, 'ref' => $reference], fn (mixed $value): bool => $value !== null),
        );

        return $reference;
    }
}
