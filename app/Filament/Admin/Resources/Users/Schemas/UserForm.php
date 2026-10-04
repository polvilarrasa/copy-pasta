<?php

declare(strict_types=1);

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Enums\Role;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rule;

class UserForm
{
    /**
     * Admins edit the public identity only. Passwords are never set here: resets go through the broker, and new
     * accounts get a generated temporary password. Roles are set on creation here and changed afterwards by action.
     * The username rules mirror App\Concerns\ProfileValidationRules.
     */
    public static function configure(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('username')
                ->label(__('admin.fields.username'))
                ->required()
                ->minLength(3)
                ->maxLength(30)
                ->regex('/^[a-zA-Z0-9_]+$/')
                ->rules(fn (?User $record): array => [
                    Rule::unique(User::class, 'username')->ignore($record),
                ]),
            TextInput::make('email')
                ->label(__('admin.fields.email'))
                ->required()
                ->email()
                ->maxLength(255)
                ->rules(fn (?User $record): array => [
                    Rule::unique(User::class, 'email')->ignore($record),
                ]),
            Select::make('role')
                ->label(__('admin.fields.role'))
                ->options(collect(Role::cases())
                    ->mapWithKeys(fn (Role $role): array => [$role->value => __('admin.roles.'.$role->value)])
                    ->all())
                ->default(Role::User->value)
                ->required()
                ->visible(fn (?User $record): bool => $record === null)
                ->dehydrated(fn (?User $record): bool => $record === null),
        ]);
    }
}
