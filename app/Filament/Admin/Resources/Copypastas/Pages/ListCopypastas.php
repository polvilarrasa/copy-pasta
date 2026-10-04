<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Copypastas\Pages;

use App\Filament\Admin\Resources\Copypastas\CopypastaResource;
use Filament\Resources\Pages\ListRecords;

class ListCopypastas extends ListRecords
{
    protected static string $resource = CopypastaResource::class;
}
