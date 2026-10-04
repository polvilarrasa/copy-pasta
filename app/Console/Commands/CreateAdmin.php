<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Enums\Role;
use App\Models\User;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

#[Signature('app:create-admin {--username= : Nombre de usuario del administrador} {--email= : Email del administrador}')]
#[Description('Crea el primer administrador con el email ya verificado')]
class CreateAdmin extends Command
{
    /**
     * Without a terminal the password comes from ADMIN_PASSWORD, so the command runs unattended in deployments.
     * It is read with getenv() rather than config(): a config cache would write the password to disk.
     */
    public function handle(): int
    {
        $username = (string) ($this->option('username') ?? $this->ask('Nombre de usuario'));
        $email = (string) ($this->option('email') ?? $this->ask('Email'));
        $password = $this->input->isInteractive()
            ? $this->askPassword()
            : (string) getenv('ADMIN_PASSWORD');

        $validator = Validator::make(
            ['username' => $username, 'email' => $email, 'password' => $password],
            [
                'username' => ['required', 'string', 'min:3', 'max:30', 'regex:/^[a-zA-Z0-9_]+$/', Rule::unique(User::class, 'username')],
                'email' => ['required', 'string', 'email', 'max:255', Rule::unique(User::class, 'email')],
                'password' => ['required', 'string', Password::min(12)->letters()->numbers()],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = new User;
        $admin->forceFill([
            'username' => $username,
            'email' => $email,
            'password' => $password,
            'role' => Role::Admin,
            'is_owner' => ! User::query()->where('is_owner', true)->exists(),
            'email_verified_at' => now(),
        ])->save();

        $this->info(__('admin.commands.admin_created', ['username' => $username]));

        return self::SUCCESS;
    }

    /**
     * Asks twice; a mismatch returns an empty password, which the validator then rejects.
     */
    private function askPassword(): string
    {
        $password = (string) $this->secret('Contraseña (mínimo 12 caracteres)');
        $confirmation = (string) $this->secret('Repite la contraseña');

        if ($password !== $confirmation) {
            $this->error(__('admin.commands.password_mismatch'));

            return '';
        }

        return $password;
    }
}
