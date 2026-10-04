<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Pages;

use App\Actions\CreateUserByAdmin;
use App\Enums\Role;
use App\Filament\Admin\Resources\Users\UserModerationActions;
use App\Filament\Admin\Resources\Users\UserResource;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    /**
     * The member receives the invitation by email, so no password ever reaches the admin.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $user = app(CreateUserByAdmin::class)->handle(
            UserModerationActions::actor(),
            (string) $data['username'],
            (string) $data['email'],
            Role::from((string) $data['role']),
        );

        Notification::make()
            ->success()
            ->title(__('admin.users.create.created'))
            ->body(__('admin.users.create.invitation_sent', ['email' => $user->email]))
            ->send();

        return $user;
    }
}
