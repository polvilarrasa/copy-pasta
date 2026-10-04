<?php

declare(strict_types=1);

use App\Actions\CreateUserByAdmin;
use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Hash;

test('un admin crea un usuario con contraseña temporal, email verificado y el rol elegido', function (): void {
    $admin = User::factory()->admin()->create();

    $result = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::Moderator);

    $user = $result['user']->refresh();

    expect($user->username)->toBe('nuevo')
        ->and($user->role)->toBe(Role::Moderator)
        ->and($user->must_change_password)->toBeTrue()
        ->and($user->hasVerifiedEmail())->toBeTrue()
        ->and(strlen($result['temporary_password']))->toBe(CreateUserByAdmin::TEMPORARY_PASSWORD_LENGTH)
        ->and(Hash::check($result['temporary_password'], $user->password))->toBeTrue();
});

test('la creación queda registrada en el log de moderación', function (): void {
    $admin = User::factory()->admin()->create();

    $result = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    expect(ModerationAction::query()->where('action', ModerationActionType::UserCreated)->sole())
        ->actor_id->toBe($admin->getKey())
        ->subject_id->toEqual($result['user']->getKey())
        ->meta->toBe(['role' => Role::User->value]);
});

test('un moderador no puede crear usuarios', function (): void {
    $moderator = User::factory()->moderator()->create();

    app(CreateUserByAdmin::class)->handle($moderator, 'nuevo', 'nuevo@example.com', Role::User);
})->throws(AuthorizationException::class);
