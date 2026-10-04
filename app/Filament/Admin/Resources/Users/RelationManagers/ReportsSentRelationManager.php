<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ReportsSentRelationManager extends RelationManager
{
    protected static string $relationship = 'reportsSent';

    protected static ?string $recordTitleAttribute = 'id';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.users.tabs.reports_sent');
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('admin.users.empty.reports_sent'))
            ->columns([
                TextColumn::make('copypasta.title')
                    ->label(__('moderation.log.copypasta'))
                    ->placeholder('—')
                    ->limit(60),
                TextColumn::make('reason')
                    ->label(__('moderation.log.reason'))
                    ->badge()
                    ->formatStateUsing(fn (ReportReason $state): string => __('moderation.reasons.'.$state->value)),
                TextColumn::make('status')
                    ->label(__('moderation.log.status'))
                    ->badge()
                    ->formatStateUsing(fn (ReportStatus $state): string => __('moderation.statuses.'.$state->value)),
                TextColumn::make('created_at')
                    ->label(__('moderation.log.created_at'))
                    ->dateTime(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
