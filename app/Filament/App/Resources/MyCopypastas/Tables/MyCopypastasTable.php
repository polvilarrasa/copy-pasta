<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\MyCopypastas\Tables;

use App\Models\Copypasta;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class MyCopypastasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.fields.title'))
                    ->searchable()
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->getStateUsing(fn (Copypasta $copypasta): string => $copypasta->isHidden() ? 'hidden' : 'visible')
                    ->formatStateUsing(fn (string $state): string => __('app.status.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'hidden' ? 'warning' : 'success')
                    ->description(fn (Copypasta $copypasta): ?string => $copypasta->isHidden()
                        ? __('app.show.hidden_notice', ['reason' => $copypasta->hidden_reason ?? ''])
                        : null),
                IconColumn::make('is_nsfw')
                    ->label(__('app.fields.is_nsfw'))
                    ->boolean(),
                TextColumn::make('score')
                    ->label(__('app.fields.score'))
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('app.fields.published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->defaultSort('published_at', 'desc')
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ]);
    }
}
