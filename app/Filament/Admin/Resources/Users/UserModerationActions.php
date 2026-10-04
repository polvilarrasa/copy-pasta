<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users;

use App\Actions\BanUser;
use App\Actions\ChangeUserRole;
use App\Actions\DeleteUser;
use App\Actions\ImpersonateUser;
use App\Actions\ResendEmailVerification;
use App\Actions\RestoreUser;
use App\Actions\SendPasswordReset;
use App\Actions\SetUserEmailVerification;
use App\Actions\UnbanUser;
use App\Enums\Role;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

/**
 * The moderation actions on a member, shared by the users table and the user's view page.
 * Every action is authorized through UserPolicy and the Action classes check it again.
 */
class UserModerationActions
{
    /**
     * Soft-deleted members only offer restore; every other action is hidden for them.
     *
     * @return array<int, Action>
     */
    public static function all(): array
    {
        $activeMemberActions = [
            self::ban(),
            self::unban(),
            self::changeRole(),
            self::sendPasswordReset(),
            self::impersonate(),
            self::verifyEmail(),
            self::unverifyEmail(),
            self::resendVerification(),
            self::delete(),
        ];

        return [
            ...array_map(
                fn (Action $action): Action => $action->hidden(fn (User $record): bool => $record->trashed()),
                $activeMemberActions,
            ),
            self::restore(),
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

    public static function verifyEmail(): Action
    {
        return Action::make('verifyEmail')
            ->label(__('admin.users.actions.verify_email'))
            ->icon(Heroicon::OutlinedCheckBadge)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.verify_email_confirm'))
            ->authorize('verifyEmail')
            ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
            ->action(fn (User $record) => app(SetUserEmailVerification::class)
                ->handle(self::actor(), $record, true));
    }

    public static function unverifyEmail(): Action
    {
        return Action::make('unverifyEmail')
            ->label(__('admin.users.actions.unverify_email'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.unverify_email_confirm'))
            ->authorize('verifyEmail')
            ->visible(fn (User $record): bool => $record->hasVerifiedEmail())
            ->action(fn (User $record) => app(SetUserEmailVerification::class)
                ->handle(self::actor(), $record, false));
    }

    public static function resendVerification(): Action
    {
        return Action::make('resendVerification')
            ->label(__('admin.users.actions.resend_verification'))
            ->icon(Heroicon::OutlinedEnvelope)
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.resend_verification_confirm'))
            ->authorize('resendVerification')
            ->visible(fn (User $record): bool => ! $record->hasVerifiedEmail())
            ->action(function (User $record): void {
                try {
                    $sent = app(ResendEmailVerification::class)->handle(self::actor(), $record);
                } catch (ThrottleRequestsException $exception) {
                    Notification::make()->danger()->title($exception->getMessage())->send();

                    return;
                }

                $sent
                    ? Notification::make()->success()->title(__('admin.users.actions.resend_verification_sent'))->send()
                    : Notification::make()->danger()->title(__('admin.users.actions.resend_verification_failed'))->send();
            });
    }

    public static function delete(): Action
    {
        return Action::make('delete')
            ->label(__('admin.users.actions.delete'))
            ->icon(Heroicon::OutlinedTrash)
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading(__('admin.users.actions.delete_heading'))
            ->modalDescription(__('admin.users.actions.delete_confirm'))
            ->authorize('delete')
            ->action(fn (User $record) => app(DeleteUser::class)->handle(self::actor(), $record));
    }

    public static function restore(): Action
    {
        return Action::make('restore')
            ->label(__('admin.users.actions.restore'))
            ->icon(Heroicon::OutlinedArrowUturnLeft)
            ->color('success')
            ->requiresConfirmation()
            ->modalDescription(__('admin.users.actions.restore_confirm'))
            ->authorize('restore')
            ->visible(fn (User $record): bool => $record->trashed())
            ->action(fn (User $record) => app(RestoreUser::class)->handle(self::actor(), $record));
    }

    public static function actor(): User
    {
        $user = auth()->user();

        throw_unless($user instanceof User, AuthenticationException::class);

        return $user;
    }
}
