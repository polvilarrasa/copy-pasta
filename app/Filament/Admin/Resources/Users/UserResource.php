<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Filament\Admin\Resources\Users\Pages\EditUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\Pages\ViewUser;
use App\Filament\Admin\Resources\Users\RelationManagers\CopypastasRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\ModerationLogRelationManager;
use App\Filament\Admin\Resources\Users\RelationManagers\ReportsSentRelationManager;
use App\Filament\Admin\Resources\Users\Schemas\UserForm;
use App\Filament\Admin\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static ?string $slug = 'usuarios';

    protected static ?string $recordTitleAttribute = 'username';

    public static function getModelLabel(): string
    {
        return __('admin.models.user');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.users');
    }

    public static function form(Schema $schema): Schema
    {
        return UserForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return UsersTable::configure($table);
    }

    /**
     * Each tab on the view page is one relation manager: their copy-pastas, reports sent and moderation log.
     */
    public static function getRelations(): array
    {
        return [
            CopypastasRelationManager::class,
            ReportsSentRelationManager::class,
            ModerationLogRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'view' => ViewUser::route('/{record}'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
