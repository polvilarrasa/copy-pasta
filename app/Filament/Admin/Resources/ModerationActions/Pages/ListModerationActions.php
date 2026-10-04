<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ModerationActions\Pages;

use App\Filament\Admin\Resources\ModerationActions\ModerationActionResource;
use Filament\Resources\Pages\ListRecords;

class ListModerationActions extends ListRecords
{
    protected static string $resource = ModerationActionResource::class;
}
