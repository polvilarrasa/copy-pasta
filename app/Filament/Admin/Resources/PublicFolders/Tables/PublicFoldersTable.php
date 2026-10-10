<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\PublicFolders\Tables;

use App\Actions\MakeFolderPrivate;
use App\Models\Folder;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;

class PublicFoldersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label(__('admin.fields.name'))
                    ->searchable()
                    ->limit(60),
                TextColumn::make('description')
                    ->label(__('admin.fields.description'))
                    ->limit(60)
                    ->toggleable(),
                TextColumn::make('user.username')
                    ->label(__('admin.fields.owner'))
                    ->searchable(),
                TextColumn::make('copypastas_count')
                    ->label(__('admin.fields.copypastas_count'))
                    ->sortable(),
                TextColumn::make('public_id')
                    ->label(__('admin.fields.public_url'))
                    ->formatStateUsing(fn (Folder $folder): string => (string) $folder->publicUrl())
                    ->url(fn (Folder $folder): ?string => $folder->publicUrl(), shouldOpenInNewTab: true),
                TextColumn::make('updated_at')
                    ->label(__('admin.fields.updated_at'))
                    ->dateTime()
                    ->sortable(),
            ])
            ->recordActions([
                Action::make('makePrivate')
                    ->label(__('admin.actions.make_folder_private'))
                    ->icon(Heroicon::OutlinedLockClosed)
                    ->color('danger')
                    ->modalHeading(__('admin.actions.make_folder_private_heading'))
                    ->modalSubmitActionLabel(__('admin.actions.make_folder_private_submit'))
                    ->authorize('makePrivate')
                    ->schema([
                        Textarea::make('reason')
                            ->label(__('admin.fields.reason'))
                            ->required()
                            ->maxLength(500),
                    ])
                    ->action(fn (array $data, Folder $folder): Folder => app(MakeFolderPrivate::class)
                        ->handle(self::actor(), $folder, (string) $data['reason'])),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
