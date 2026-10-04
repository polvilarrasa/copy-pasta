<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\ModerationActionType;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

/**
 * Log entries about this member: bans, role changes, resets and impersonations.
 */
class ModerationLogRelationManager extends RelationManager
{
    protected static string $relationship = 'subjectActions';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.users.tabs.moderation_log');
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('admin.users.empty.moderation_log'))
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime(),
                TextColumn::make('actor.username')
                    ->label(__('admin.fields.actor'))
                    ->placeholder('—'),
                TextColumn::make('action')
                    ->label(__('admin.fields.action'))
                    ->badge()
                    ->formatStateUsing(fn (ModerationActionType $state): string => __('admin.moderation_actions.'.$state->value)),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->placeholder('—')
                    ->limit(80),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
