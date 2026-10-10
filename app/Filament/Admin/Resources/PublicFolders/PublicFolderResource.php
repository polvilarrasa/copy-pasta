<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PublicFolders;

use App\Filament\Admin\Resources\PublicFolders\Pages\ListPublicFolders;
use App\Filament\Admin\Resources\PublicFolders\Tables\PublicFoldersTable;
use App\Models\Folder;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class PublicFolderResource extends Resource
{
    protected static ?string $model = Folder::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFolderOpen;

    protected static ?string $slug = 'carpetas-publicas';

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('admin.models.public_folder');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.public_folders');
    }

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user instanceof User && $user->isStaff();
    }

    public static function canCreate(): bool
    {
        return false;
    }

    /**
     * The folders that are public right now and those the staff made private and locked, so that the lock can be lifted.
     * A folder the staff unlocked, or its owner made private, leaves the list.
     *
     * @return Builder<Folder>
     */
    public static function getEloquentQuery(): Builder
    {
        return Folder::query()
            ->where(fn (Builder $query) => $query->where('is_public', true)->orWhereNotNull('public_locked_at'))
            ->with('user:id,username')
            ->withCount('copypastas');
    }

    public static function table(Table $table): Table
    {
        return PublicFoldersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPublicFolders::route('/'),
        ];
    }
}
