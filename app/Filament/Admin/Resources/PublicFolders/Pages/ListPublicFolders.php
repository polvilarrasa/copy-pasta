<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PublicFolders\Pages;

use App\Filament\Admin\Resources\PublicFolders\PublicFolderResource;
use Filament\Resources\Pages\ListRecords;

class ListPublicFolders extends ListRecords
{
    protected static string $resource = PublicFolderResource::class;
}
