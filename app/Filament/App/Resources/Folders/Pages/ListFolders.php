<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Folders\Pages;

use App\Filament\App\Resources\Folders\FolderResource;
use Filament\Resources\Pages\ListRecords;

class ListFolders extends ListRecords
{
    protected static string $resource = FolderResource::class;
}
