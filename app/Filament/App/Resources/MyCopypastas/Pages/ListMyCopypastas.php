<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas\Pages;

use App\Filament\App\Resources\MyCopypastas\MyCopypastaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMyCopypastas extends ListRecords
{
    protected static string $resource = MyCopypastaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
