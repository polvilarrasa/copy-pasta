<?php

declare(strict_types=1);

use App\Actions\DeleteUser;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un admin borra lógicamente a un miembro y el registro sigue en la base de datos', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(DeleteUser::class)->handle($admin, $member);

    expect(User::query()->find($member->getKey()))->toBeNull()
        ->and(User::withTrashed()->find($member->getKey())?->trashed())->toBeTrue();
});

test('el borrado queda registrado en el log de moderación', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(DeleteUser::class)->handle($admin, $member);

    expect(ModerationAction::query()->where('action', ModerationActionType::UserDeleted)->sole()->subject_id)
        ->toEqual($member->getKey());
});

test('un admin no se borra a sí mismo', function (): void {
    $admin = User::factory()->admin()->create();

    app(DeleteUser::class)->handle($admin, $admin);
})->throws(AuthorizationException::class);

test('un moderador no puede borrar usuarios', function (): void {
    $moderator = User::factory()->moderator()->create();

    app(DeleteUser::class)->handle($moderator, User::factory()->create());
})->throws(AuthorizationException::class);
