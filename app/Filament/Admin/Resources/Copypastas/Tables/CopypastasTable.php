<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Copypastas\Tables;

use App\Models\Copypasta;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CopypastasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->searchable()
                    ->limit(60),
                TextColumn::make('user.username')
                    ->label(__('admin.fields.author'))
                    ->searchable(),
                IconColumn::make('is_hidden')
                    ->label(__('admin.fields.is_hidden'))
                    ->getStateUsing(fn (Copypasta $copypasta): bool => $copypasta->isHidden())
                    ->boolean(),
                IconColumn::make('is_nsfw')
                    ->label(__('admin.fields.is_nsfw'))
                    ->boolean(),
                TextColumn::make('score')
                    ->label(__('admin.fields.score'))
                    ->sortable(),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('hidden')
                    ->label(__('admin.filters.hidden'))
                    ->trueLabel(__('admin.filters.hidden_yes'))
                    ->falseLabel(__('admin.filters.hidden_no'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('hidden_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('hidden_at'),
                    ),
                TernaryFilter::make('is_nsfw')
                    ->label(__('admin.filters.nsfw'))
                    ->trueLabel(__('admin.filters.nsfw_yes'))
                    ->falseLabel(__('admin.filters.nsfw_no')),
                SelectFilter::make('tags')
                    ->label(__('admin.fields.tags'))
                    ->relationship('tags', 'name')
                    ->multiple()
                    ->preload(),
                SelectFilter::make('user')
                    ->label(__('admin.fields.author'))
                    ->relationship('user', 'username')
                    ->searchable(),
            ])
            ->recordActions([
                CopypastaModerationActions::hide(),
                CopypastaModerationActions::restore(),
                CopypastaModerationActions::toggleNsfw(),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
