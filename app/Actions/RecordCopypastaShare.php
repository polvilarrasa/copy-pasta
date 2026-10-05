<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Str;

class RecordCopypastaShare
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * Records a share and returns the reference code the shared link carries. Each share gets its own code, so a
     * later visit with `?ref=` can be traced back to the share that produced it.
     *
     * @param  array<string, mixed>  $context
     */
    public function handle(Copypasta $copypasta, ?User $user, array $context = []): string
    {
        $reference = Str::random(8);

        $this->recordEvent->handle(EventType::Share, $user, $copypasta, [...$context, 'ref' => $reference]);

        return $reference;
    }
}
