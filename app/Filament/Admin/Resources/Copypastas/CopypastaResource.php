<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Copypastas;

use App\Filament\Admin\Resources\Copypastas\Pages\ListCopypastas;
use App\Filament\Admin\Resources\Copypastas\Tables\CopypastasTable;
use App\Models\Copypasta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CopypastaResource extends Resource
{
    protected static ?string $model = Copypasta::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getModelLabel(): string
    {
        return __('admin.models.copypasta');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.copypastas');
    }

    public static function table(Table $table): Table
    {
        return CopypastasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCopypastas::route('/'),
        ];
    }
}
