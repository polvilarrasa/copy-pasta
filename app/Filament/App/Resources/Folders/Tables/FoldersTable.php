<?php

declare(strict_types=1);

namespace App\Filament\App\Resources\Folders\Tables;

use App\Actions\CreateFolder;
use App\Actions\DeleteFolder;
use App\Actions\RenameFolder;
use App\Filament\App\Resources\Folders\FolderResource;
use App\Filament\App\Resources\Folders\Schemas\FolderForm;
use App\Models\Folder;
use App\Models\User;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;

class FoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('app.fields.folder_name'))
                    ->description(fn (Folder $folder): ?string => $folder->is_default ? __('app.folders.default_badge') : null)
                    ->url(fn (Folder $folder): string => FolderResource::getUrl('view', ['record' => $folder])),
                TextColumn::make('copypastas_count')
                    ->label(__('app.folders.count'))
                    ->counts('copypastas'),
            ])
            ->reorderable('position')
            ->beforeReordering(function (array $order): void {
                // Reordering writes positions by key, so every key must be one of the member's own folders.
                abort_unless(
                    Folder::query()->where('user_id', auth()->id())->whereKey($order)->count() === count($order),
                    403,
                );
            })
            ->headerActions([
                CreateAction::make()
                    ->label(__('app.folders.create'))
                    ->modalHeading(__('app.folders.create'))
                    ->schema(fn (): array => FolderForm::fields())
                    ->using(fn (array $data): Folder => app(CreateFolder::class)->handle(self::member(), (string) $data['name'])),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->label(__('app.folders.rename'))
                    ->modalHeading(__('app.folders.rename'))
                    ->visible(fn (Folder $folder): bool => ! $folder->is_default)
                    ->schema(fn (Folder $folder): array => FolderForm::fields($folder))
                    ->using(fn (Folder $folder, array $data): Folder => app(RenameFolder::class)
                        ->handle(self::member(), $folder, (string) $data['name'])),
                DeleteAction::make()
                    ->label(__('app.folders.delete'))
                    ->visible(fn (Folder $folder): bool => ! $folder->is_default)
                    ->requiresConfirmation()
                    ->modalDescription(__('app.folders.delete_confirm'))
                    ->action(fn (Folder $folder) => app(DeleteFolder::class)->handle(self::member(), $folder)),
            ]);
    }

    private static function member(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
