<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Actions\BanUser;
use App\Actions\ChangeUserRole;
use App\Actions\ImpersonateUser;
use App\Actions\SendPasswordReset;
use App\Actions\UnbanUser;
use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\AuthenticationException;

/**
 * The moderation actions on a member, shared by the users table and the user's view page.
 * Every action is authorized through UserPolicy and the Action classes check it again.
 */
class UserModerationActions
{
    /**
     * @return array<int, Action>
     */
    public static function all(): array
    {
        return [
            self::ban(),
            self::unban(),
            self::changeRole(),
            self::sendPasswordReset(),
            self::impersonate(),
        ];
    }

    public static function ban(): Action
    {
        return Action::make('ban')
            ->label(__('admin.users.actions.ban'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->modalHeading(__('admin.users.actions.ban_heading'))
            ->modalSubmitActionLabel(__('admin.users.actions.ban_submit'))
            ->authorize('ban')
            ->visible(fn (User $record): bool => ! $record->isBanned())
            ->schema([
                Textarea::make('reason')
                    ->label(__('admin.fields.reason'))
                    ->required()
                    ->maxLength(500),
            ])
            ->action(fn (array $data, User $record) => app(BanUser::class)
                ->handle(self::actor(), $record, (string) $data['reason']));
    }

    public static function unban(): Action
    {
        return Action::make('unban')
            ->label(__('admin.users.actions.unban'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->requiresConfirmation()
            ->authorize('unban')
            ->visible(fn (User $record): bool => $record->isBanned())
            ->action(fn (User $record) => app(UnbanUser::class)->handle(self::actor(), $record));
    }

    public static function changeRole(): Action
    {
        return Action::make('changeRole')
            ->label(__('admin.users.actions.change_role'))
            ->icon(Heroicon::OutlinedUserGroup)
            ->modalHeading(__('admin.users.actions.change_role_heading'))
            ->authorize('changeRole')
            ->schema(fn (User $record): array => [
                Select::make('role')
                    ->label(__('admin.fields.role'))
                    ->options(collect(Role::cases())
                        ->mapWithKeys(fn (Role $role): array => [$role->value => __('admin.roles.'.$role->value)])
                        ->all())
                    ->default(fn (User $record): string => $record->role->value)
                    ->required(),
            ])
            ->action(fn (array $data, User $record) => app(ChangeUserRole::class)
                ->handle(self::actor(), $record, Role::from((string) $data['role'])));
    }

    public static function sendPasswordReset(): Action
    {
        return Action::make('sendPasswordReset')
            ->label(__('admin.users.actions.send_reset'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.send_reset_confirm'))
            ->authorize('sendPasswordReset')
            ->action(function (User $record): void {
                $sent = app(SendPasswordReset::class)->handle(self::actor(), $record);

                $notification = Notification::make()
                    ->title($sent ? __('admin.users.actions.send_reset_sent') : __('admin.users.actions.send_reset_failed'));

                $sent ? $notification->success() : $notification->danger();

                $notification->send();
            });
    }

    public static function impersonate(): Action
    {
        return Action::make('impersonate')
            ->label(__('admin.users.actions.impersonate'))
            ->icon(Heroicon::OutlinedArrowRightOnRectangle)
            ->color('warning')
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.impersonate_confirm'))
            ->authorize('impersonate')
            ->action(function (User $record) {
                app(ImpersonateUser::class)->handle(self::actor(), $record);

                return redirect()->to(url('/app'));
            });
    }

    private static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
