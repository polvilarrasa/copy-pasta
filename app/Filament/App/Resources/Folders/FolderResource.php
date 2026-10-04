<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Folders;

use App\Filament\App\Resources\Folders\Pages\ListFolders;
use App\Filament\App\Resources\Folders\Pages\ViewFolder;
use App\Filament\App\Resources\Folders\RelationManagers\CopypastasRelationManager;
use App\Filament\App\Resources\Folders\Schemas\FolderForm;
use App\Filament\App\Resources\Folders\Tables\FoldersTable;
use App\Models\Folder;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Gate;

class FolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolder;

    protected static ?string $slug = 'carpetas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('app.models.folder');
    }

    public static function getPluralModelLabel(): string
    {
        return __('app.models.folders');
    }

    /**
     * Same access rule as the copy-pasta list: verified members who are not banned.
     */
    public static function canViewAny(): bool
    {
        return Gate::allows('viewAny', Folder::class);
    }

    /**
     * Members only reach their own folders, so other people's folders return 404. Favoritos comes first on
     * equal positions so the reorder handle never sorts it below an empty folder.
     */
    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('user_id', auth()->id())
            ->orderBy('position')
            ->orderBy('name');
    }

    public static function form(Schema $schema): Schema
    {
        return FolderForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FoldersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CopypastasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFolders::route('/'),
            'view' => ViewFolder::route('/{record}'),
        ];
    }
}
