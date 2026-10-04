<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ModerationActions;

use App\Filament\Admin\Resources\ModerationActions\Pages\ListModerationActions;
use App\Filament\Admin\Resources\ModerationActions\Tables\ModerationActionsTable;
use App\Models\ModerationAction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModerationActionResource extends Resource
{
    protected static ?string $model = ModerationAction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    public static function getModelLabel(): string
    {
        return __('admin.models.moderation_action');
    }

    public static function getPluralModelLabel(): string
    {
        return __('admin.models.moderation_actions');
    }

    public static function table(Table $table): Table
    {
        return ModerationActionsTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('actor');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationActions::route('/'),
        ];
    }
}
