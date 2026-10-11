<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\User;

class RecordAppInstall
{
    public function __construct(private RecordEvent $recordEvent) {}

    /**
     * The browser reported that the app was installed (`appinstalled`). Anonymous visitors count too, with their
     * daily visitor hash.
     */
    public function handle(?User $user = null): void
    {
        $this->recordEvent->handle(EventType::AppInstall, $user);
    }
}
