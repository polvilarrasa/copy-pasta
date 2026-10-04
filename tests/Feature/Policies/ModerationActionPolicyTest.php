<?php

declare(strict_types=1);

use App\Models\ModerationAction;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario normal no ve el log de moderación', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', ModerationAction::class))->toBeFalse();
});

test('un moderador y un admin ven el log de moderación', function (): void {
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($moderator)->allows('viewAny', ModerationAction::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('viewAny', ModerationAction::class))->toBeTrue();
});
