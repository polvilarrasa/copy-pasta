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
     * The temporary password is shown once, in a notification that stays until the admin closes it.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordCreation(array $data): Model
    {
        $created = app(CreateUserByAdmin::class)->handle(
            UserModerationActions::actor(),
            (string) $data['username'],
            (string) $data['email'],
            Role::from((string) $data['role']),
        );

        Notification::make()
            ->success()
            ->title(__('admin.users.create.created'))
            ->body(__('admin.users.create.temporary_password', ['password' => $created['temporary_password']]))
            ->persistent()
            ->send();

        return $created['user'];
    }
}
