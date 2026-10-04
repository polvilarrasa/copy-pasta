<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\UserModerationActions;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('username')
                    ->label(__('admin.fields.username'))
                    ->searchable(),
                TextColumn::make('email')
                    ->label(__('admin.fields.email'))
                    ->searchable(),
                TextColumn::make('role')
                    ->label(__('admin.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (Role $state): string => __('admin.roles.'.$state->value)),
                TextColumn::make('banned_at')
                    ->label(__('admin.fields.banned'))
                    ->placeholder('—')
                    ->dateTime(),
                TextColumn::make('created_at')
                    ->label(__('admin.fields.created_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label(__('admin.fields.role'))
                    ->options(fn (): array => collect(Role::cases())
                        ->mapWithKeys(fn (Role $role): array => [$role->value => __('admin.roles.'.$role->value)])
                        ->all()),
                TernaryFilter::make('banned')
                    ->label(__('admin.filters.banned'))
                    ->trueLabel(__('admin.filters.banned_yes'))
                    ->falseLabel(__('admin.filters.banned_no'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('banned_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('banned_at'),
                    ),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ...UserModerationActions::all(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
