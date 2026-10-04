<?php

declare(strict_types=1);

use App\Actions\CreateUserByAdmin;
use App\Enums\ModerationActionType;
use App\Enums\Role;
use App\Mail\UserInvitationMail;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Mail;

test('un admin crea un usuario sin verificar, con el rol elegido', function (): void {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    $member = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::Moderator);

    expect($member->role)->toBe(Role::Moderator)
        ->and($member->email_verified_at)->toBeNull();
});

test('la invitación se envía a la dirección del nuevo usuario', function (): void {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    Mail::assertQueued(UserInvitationMail::class, fn (UserInvitationMail $mail): bool => $mail->hasTo('nuevo@example.com'));
});

test('la creación queda registrada en el log de moderación', function (): void {
    Mail::fake();
    $admin = User::factory()->admin()->create();

    $member = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    expect(ModerationAction::query()->where('action', ModerationActionType::UserCreated)->sole()->subject_id)
        ->toEqual($member->getKey());
});

test('un moderador no puede crear usuarios', function (): void {
    Mail::fake();

    app(CreateUserByAdmin::class)->handle(User::factory()->moderator()->create(), 'nuevo', 'nuevo@example.com', Role::User);
})->throws(AuthorizationException::class);
