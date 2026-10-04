<?php

declare(strict_types=1);

use App\Actions\BanUser;
use App\Enums\ModerationActionType;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('un admin banea a un miembro, guarda el motivo y registra la acción', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(BanUser::class)->handle($admin, $member, 'Acoso reiterado');

    $member->refresh();

    expect($member->isBanned())->toBeTrue()
        ->and($member->ban_reason)->toBe('Acoso reiterado');

    $this->assertDatabaseHas('moderation_actions', [
        'actor_id' => $admin->id,
        'action' => ModerationActionType::Ban->value,
        'subject_type' => User::class,
        'subject_id' => $member->id,
        'reason' => 'Acoso reiterado',
    ]);
});

test('banear exige un motivo no vacío', function (): void {
    app(BanUser::class)->handle(User::factory()->admin()->create(), User::factory()->create(), '   ');
})->throws(ValidationException::class);

test('un moderador no puede banear', function (): void {
    app(BanUser::class)->handle(User::factory()->moderator()->create(), User::factory()->create(), 'Motivo');
})->throws(AuthorizationException::class);

test('un admin no puede banearse a sí mismo', function (): void {
    $admin = User::factory()->admin()->create();

    app(BanUser::class)->handle($admin, $admin, 'Motivo');
})->throws(AuthorizationException::class);

test('un usuario baneado no puede iniciar sesión', function (): void {
    $member = User::factory()->banned('Spam')->create(['email' => 'baneado@example.com']);

    $this->post('/login', ['email' => $member->email, 'password' => 'password'])
        ->assertSessionHasErrors();

    $this->assertGuest();
});

test('un miembro baneado con sesión abierta se desconecta en la siguiente petición', function (): void {
    $member = User::factory()->create();
    $this->actingAs($member);

    app(BanUser::class)->handle(User::factory()->admin()->create(), $member, 'Motivo');

    $this->get('/app')->assertRedirect(route('login'));
    $this->assertGuest();
});
