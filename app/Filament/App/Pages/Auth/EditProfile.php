<?php

declare(strict_types=1);

namespace App\Filament\App\Pages\Auth;

use App\Concerns\ProfileValidationRules;
use App\Models\User;
use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Model;

class EditProfile extends BaseEditProfile
{
    use ProfileValidationRules;

    private bool $emailChanged = false;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getUsernameFormComponent(),
                $this->getEmailFormComponent()->disabled(fn (): bool => is_impersonating()),
                $this->getShowNsfwFormComponent(),
                $this->getPasswordFormComponent()->disabled(fn (): bool => is_impersonating()),
                $this->getPasswordConfirmationFormComponent()->disabled(fn (): bool => is_impersonating()),
                $this->getCurrentPasswordFormComponent()->disabled(fn (): bool => is_impersonating()),
            ]);
    }

    /**
     * A new email must be verified again before the user panel opens.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        $changesEmailOrPassword = (array_key_exists('email', $data) && $data['email'] !== $record->getAttributeValue('email'))
            || filled($data['password'] ?? null);

        abort_if(is_impersonating() && $changesEmailOrPassword, 403);

        $this->emailChanged = array_key_exists('email', $data)
            && $data['email'] !== $record->getAttributeValue('email');

        parent::handleRecordUpdate($record, $data);

        if ($this->emailChanged && $record instanceof User) {
            $record->forceFill(['email_verified_at' => null])->save();
            $record->sendEmailVerificationNotification();
        }

        return $record;
    }

    protected function getRedirectUrl(): ?string
    {
        return $this->emailChanged ? route('verification.notice') : null;
    }

    protected function getUsernameFormComponent(): Component
    {
        return TextInput::make('username')
            ->label(__('app.profile.username'))
            ->rules($this->usernameRules($this->getUser()->getKey()))
            ->autofocus();
    }

    protected function getShowNsfwFormComponent(): Component
    {
        return Toggle::make('show_nsfw')
            ->label(__('app.profile.show_nsfw'))
            ->helperText(__('app.profile.show_nsfw_helper'));
    }
}
