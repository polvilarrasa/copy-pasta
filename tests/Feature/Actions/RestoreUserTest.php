<?php

declare(strict_types=1);

use App\Actions\RestoreUser;
use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\User;

test('un admin restaura a un miembro borrado lógicamente', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->delete();

    app(RestoreUser::class)->handle($admin, $member);

    expect(User::query()->find($member->getKey()))->not->toBeNull()
        ->and(ModerationAction::query()->where('action', ModerationActionType::UserRestored)->count())->toBe(1);
});

test('restaurar a un usuario que no está borrado no hace nada', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    app(RestoreUser::class)->handle($admin, $member);

    expect(ModerationAction::query()->where('action', ModerationActionType::UserRestored)->count())->toBe(0);
});
