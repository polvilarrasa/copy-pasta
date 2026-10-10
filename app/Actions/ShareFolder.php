<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Folder;
use App\Models\User;

class ShareFolder
{
    public function __construct(
        private SetFolderVisibility $setVisibility,
        private RecordEvent $recordEvent,
    ) {}

    /**
     * Prepares a folder to be shared and returns its public address. A private folder is made public first: the owner
     * confirms that in the interface before calling this. The share is recorded as an event.
     */
    public function handle(User $user, Folder $folder): string
    {
        $folder = $this->setVisibility->handle($user, $folder, true);

        $this->recordEvent->handle(EventType::FolderShare, $user);

        return (string) $folder->publicUrl();
    }
}
