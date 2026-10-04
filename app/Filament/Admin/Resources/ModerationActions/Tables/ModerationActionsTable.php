<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\ModerationActions\Tables;

use App\Enums\ModerationActionType;
use App\Models\Copypasta;
use App\Models\Tag;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class ModerationActionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('actor.username')
                    ->label(__('admin.fields.actor'))
                    ->placeholder('—')
                    ->searchable(),
                TextColumn::make('action')
                    ->label(__('admin.fields.action'))
                    ->badge()
                    ->formatStateUsing(fn (ModerationActionType $state): string => __('admin.moderation_actions.'.$state->value)),
                TextColumn::make('subject_type')
                    ->label(__('admin.fields.subject'))
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        Copypasta::class => __('admin.models.copypasta'),
                        Tag::class => __('admin.models.tag'),
                        default => $state,
                    }),
                TextColumn::make('subject_id')
                    ->label('ID')
                    ->searchable(),
                TextColumn::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->limit(80)
                    ->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
