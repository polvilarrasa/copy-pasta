<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Copypastas\Tables;

use App\Actions\HideCopypasta;
use App\Actions\MarkCopypastaNsfw;
use App\Actions\RestoreCopypasta;
use App\Models\Copypasta;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
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
                Action::make('hide')
                    ->label(__('admin.actions.hide'))
                    ->icon(Heroicon::OutlinedEyeSlash)
                    ->color('danger')
                    ->modalHeading(__('admin.actions.hide_heading'))
                    ->modalSubmitActionLabel(__('admin.actions.hide_submit'))
                    ->authorize('hide')
                    ->visible(fn (Copypasta $copypasta): bool => ! $copypasta->isHidden())
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('admin.fields.reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(fn (array $data, Copypasta $copypasta): Copypasta => app(HideCopypasta::class)
                        ->handle(self::actor(), $copypasta, (string) $data['reason'])),
                Action::make('restore')
                    ->label(__('admin.actions.restore'))
                    ->icon(Heroicon::OutlinedEye)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(__('admin.actions.restore_heading'))
                    ->authorize('restore')
                    ->visible(fn (Copypasta $copypasta): bool => $copypasta->isHidden())
                    ->action(fn (Copypasta $copypasta): Copypasta => app(RestoreCopypasta::class)
                        ->handle(self::actor(), $copypasta)),
                Action::make('toggleNsfw')
                    ->label(fn (Copypasta $copypasta): string => $copypasta->is_nsfw
                        ? __('admin.actions.unmark_nsfw')
                        : __('admin.actions.mark_nsfw'))
                    ->icon(Heroicon::OutlinedNoSymbol)
                    ->authorize('markNsfw')
                    ->action(fn (Copypasta $copypasta): Copypasta => app(MarkCopypastaNsfw::class)
                        ->handle(self::actor(), $copypasta, ! $copypasta->is_nsfw)),
            ])
            ->defaultSort('published_at', 'desc');
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
