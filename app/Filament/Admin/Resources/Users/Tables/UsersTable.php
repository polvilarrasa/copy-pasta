<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Tables;

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\UserModerationActions;
use App\Models\User;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
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
                    ->visible(fn (): bool => auth()->user()?->isAdmin() === true)
                    ->searchable(fn (): bool => auth()->user()?->isAdmin() === true),
                TextColumn::make('role')
                    ->label(__('admin.fields.role'))
                    ->badge()
                    ->formatStateUsing(fn (Role $state): string => __('admin.roles.'.$state->value)),
                IconColumn::make('email_verified')
                    ->label(__('admin.fields.verified'))
                    ->getStateUsing(fn (User $record): bool => $record->hasVerifiedEmail())
                    ->boolean(),
                TextColumn::make('banned_at')
                    ->label(__('admin.fields.banned'))
                    ->placeholder('—')
                    ->dateTime(),
                TextColumn::make('deleted_at')
                    ->label(__('admin.fields.deleted'))
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
                TernaryFilter::make('verified')
                    ->label(__('admin.filters.verified'))
                    ->trueLabel(__('admin.filters.verified_yes'))
                    ->falseLabel(__('admin.filters.verified_no'))
                    ->queries(
                        true: fn (Builder $query): Builder => $query->whereNotNull('email_verified_at'),
                        false: fn (Builder $query): Builder => $query->whereNull('email_verified_at'),
                    ),
                TrashedFilter::make(),
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
                EditAction::make()->hidden(fn (User $record): bool => $record->trashed()),
                ...UserModerationActions::all(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
