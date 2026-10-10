<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Enums\NotificationType;
use App\Models\Folder;

class FolderMadePrivateNotification extends AppNotification
{
    public function __construct(public Folder $folder, public string $reason) {}

    public function type(): NotificationType
    {
        return NotificationType::FolderMadePrivate;
    }

    /**
     * The name the folder had is kept: the owner can rename it later, and the reason belongs to what the staff saw.
     *
     * @return array{folder_id: int, name: string, reason: string}
     */
    protected function payload(): array
    {
        return ['folder_id' => $this->folder->getKey(), 'name' => $this->folder->name, 'reason' => $this->reason];
    }
}
