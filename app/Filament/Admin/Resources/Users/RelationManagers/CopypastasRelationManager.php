<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Models\Copypasta;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CopypastasRelationManager extends RelationManager
{
    protected static string $relationship = 'copypastas';

    protected static ?string $recordTitleAttribute = 'title';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('admin.users.tabs.copypastas');
    }

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->emptyStateHeading(__('admin.users.empty.copypastas'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('admin.fields.title'))
                    ->limit(60),
                TextColumn::make('status')
                    ->label(__('admin.fields.status'))
                    ->getStateUsing(fn (Copypasta $copypasta): string => match (true) {
                        $copypasta->trashed() => 'deleted',
                        $copypasta->isHidden() => 'hidden',
                        default => 'visible',
                    })
                    ->formatStateUsing(fn (string $state): string => __('admin.users.copypasta_status.'.$state))
                    ->badge(),
                TextColumn::make('score')
                    ->label(__('admin.fields.score')),
                TextColumn::make('published_at')
                    ->label(__('admin.fields.published_at'))
                    ->dateTime()
                    ->placeholder('—'),
            ])
            ->defaultSort('published_at', 'desc');
    }
}
