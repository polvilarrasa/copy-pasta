<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\RelationManagers;

use App\Actions\RestoreAchievement;
use App\Actions\RevokeAchievement;
use App\Models\User;
use App\Models\UserAchievement;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\Model;

/**
 * The member's achievements, with revoke and restore for admins. Both need a reason and are logged.
 */
class AchievementsRelationManager extends RelationManager
{
    protected static string $relationship = 'achievements';

    protected static ?string $recordTitleAttribute = 'achievement_key';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('achievements.admin.tab');
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof User && auth()->user()?->can('manageAchievements', $ownerRecord) === true;
    }

    public function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('achievements.admin.empty'))
            ->columns([
                TextColumn::make('achievement_key')
                    ->label(__('achievements.admin.achievement'))
                    ->formatStateUsing(fn (string $state, UserAchievement $record): string => $record->achievement()?->name() ?? $state),
                TextColumn::make('unlocked_at')
                    ->label(__('achievements.admin.unlocked_at'))
                    ->dateTime(),
                TextColumn::make('state')
                    ->label(__('achievements.admin.state'))
                    ->badge()
                    ->getStateUsing(fn (UserAchievement $record): string => $record->isRevoked() ? __('achievements.admin.revoked') : __('achievements.admin.active'))
                    ->color(fn (UserAchievement $record): string => $record->isRevoked() ? 'danger' : 'success'),
                TextColumn::make('revoke_reason')
                    ->label(__('admin.fields.reason'))
                    ->placeholder('—')
                    ->limit(80),
            ])
            ->recordActions([
                $this->revokeAction(),
                $this->restoreAction(),
            ])
            ->defaultSort('unlocked_at', 'desc');
    }

    private function revokeAction(): Action
    {
        return Action::make('revoke')
            ->label(__('achievements.admin.revoke'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->modalHeading(__('achievements.admin.revoke_heading'))
            ->modalDescription(__('achievements.admin.revoke_description'))
            ->modalSubmitActionLabel(__('achievements.admin.revoke_submit'))
            ->visible(fn (UserAchievement $record): bool => ! $record->isRevoked())
            ->schema([
                Textarea::make('reason')->label(__('admin.fields.reason'))->required()->maxLength(500),
            ])
            ->action(function (array $data, UserAchievement $record): void {
                app(RevokeAchievement::class)->handle(self::actor(), $record, (string) $data['reason']);

                Notification::make()->title(__('achievements.admin.revoked_notice'))->success()->send();
            });
    }

    private function restoreAction(): Action
    {
        return Action::make('restore')
            ->label(__('achievements.admin.restore'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('success')
            ->modalHeading(__('achievements.admin.restore_heading'))
            ->modalDescription(__('achievements.admin.restore_description'))
            ->modalSubmitActionLabel(__('achievements.admin.restore_submit'))
            ->visible(fn (UserAchievement $record): bool => $record->isRevoked())
            ->schema([
                Textarea::make('reason')->label(__('admin.fields.reason'))->required()->maxLength(500),
            ])
            ->action(function (array $data, UserAchievement $record): void {
                app(RestoreAchievement::class)->handle(self::actor(), $record, (string) $data['reason']);

                Notification::make()->title(__('achievements.admin.restored_notice'))->success()->send();
            });
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
