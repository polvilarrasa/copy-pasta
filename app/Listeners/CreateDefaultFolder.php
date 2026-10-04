<?php

declare(strict_types=1);

namespace App\Listeners;

use App\Models\Folder;
use Illuminate\Auth\Events\Registered;

class CreateDefaultFolder
{
    public function handle(Registered $event): void
    {
        Folder::query()->firstOrCreate(
            ['user_id' => $event->user->getAuthIdentifier(), 'is_default' => true],
            ['name' => Folder::DEFAULT_NAME, 'position' => 0],
        );
    }
}
