<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Reports\Tables;

use App\Enums\ReportReason;
use App\Enums\ReportStatus;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ReportsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('copypasta.title')
                    ->label(__('moderation.log.copypasta'))
                    ->placeholder('—')
                    ->limit(60),
                TextColumn::make('reporter.username')
                    ->label(__('moderation.log.reporter')),
                TextColumn::make('contact_email')
                    ->label(__('moderation.log.contact')),
                TextColumn::make('reason')
                    ->label(__('moderation.log.reason'))
                    ->badge()
                    ->formatStateUsing(fn (ReportReason $state): string => __('moderation.reasons.'.$state->value)),
                TextColumn::make('status')
                    ->label(__('moderation.log.status'))
                    ->badge()
                    ->color(fn (ReportStatus $state): string => match ($state) {
                        ReportStatus::Pending => 'warning',
                        ReportStatus::Accepted => 'danger',
                        ReportStatus::Rejected => 'gray',
                    })
                    ->formatStateUsing(fn (ReportStatus $state): string => __('moderation.statuses.'.$state->value)),
                TextColumn::make('created_at')
                    ->label(__('moderation.log.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resolvedBy.username')
                    ->label(__('moderation.log.resolved_by'))
                    ->placeholder('—'),
                TextColumn::make('resolved_at')
                    ->label(__('moderation.log.resolved_at'))
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('moderation.log.status'))
                    ->options(fn (): array => collect(ReportStatus::cases())
                        ->mapWithKeys(fn (ReportStatus $status): array => [$status->value => __('moderation.statuses.'.$status->value)])
                        ->all()),
                SelectFilter::make('reason')
                    ->label(__('moderation.log.reason'))
                    ->options(fn (): array => collect(ReportReason::cases())
                        ->mapWithKeys(fn (ReportReason $reason): array => [$reason->value => __('moderation.reasons.'.$reason->value)])
                        ->all()),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
