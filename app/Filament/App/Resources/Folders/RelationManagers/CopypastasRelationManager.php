<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Folders\RelationManagers;

use App\Actions\RemoveFromFolder;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CopypastasRelationManager extends RelationManager
{
    protected static string $relationship = 'copypastas';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withoutGlobalScopes([SoftDeletingScope::class]))
            ->heading(__('app.folders.contents'))
            ->emptyStateHeading(__('app.folders.empty'))
            ->columns([
                TextColumn::make('title')
                    ->label(__('app.fields.title'))
                    ->getStateUsing(fn (Copypasta $copypasta): string => $this->isRemoved($copypasta)
                        ? __('app.folders.removed_content')
                        : $copypasta->title),
                TextColumn::make('status')
                    ->label(__('app.fields.status'))
                    ->getStateUsing(fn (Copypasta $copypasta): string => match (true) {
                        $copypasta->trashed() => 'deleted',
                        $copypasta->isHidden() => 'hidden',
                        default => 'visible',
                    })
                    ->formatStateUsing(fn (string $state): string => __('app.folders.status.'.$state))
                    ->badge()
                    ->color(fn (string $state): string => $state === 'visible' ? 'success' : 'warning'),
            ])
            ->recordActions([
                Action::make('removeFromFolder')
                    ->label(__('app.folders.remove'))
                    ->icon(Heroicon::OutlinedXMark)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription(__('app.folders.remove_confirm'))
                    ->action(fn (Copypasta $copypasta) => app(RemoveFromFolder::class)
                        ->handle($this->member(), $this->folder(), $copypasta)),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('removeFromFolder')
                        ->label(__('app.folders.remove'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalDescription(__('app.folders.remove_confirm'))
                        ->action(function (Collection $copypastas): void {
                            foreach ($copypastas as $copypasta) {
                                if ($copypasta instanceof Copypasta) {
                                    app(RemoveFromFolder::class)->handle($this->member(), $this->folder(), $copypasta);
                                }
                            }
                        }),
                ]),
            ]);
    }

    /**
     * A copy-pasta that is hidden or deleted keeps its place in the folder but shows only a placeholder.
     */
    private function isRemoved(Copypasta $copypasta): bool
    {
        return $copypasta->trashed() || $copypasta->isHidden();
    }

    private function folder(): Folder
    {
        $folder = $this->getOwnerRecord();

        throw_unless($folder instanceof Folder, AuthenticationException::class);

        return $folder;
    }

    private function member(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
