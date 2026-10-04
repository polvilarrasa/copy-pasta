<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario normal no lista ni ve otros usuarios ni los edita', function (): void {
    $user = User::factory()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($user)->allows('viewAny', User::class))->toBeFalse();
    expect(Gate::forUser($user)->allows('view', $other))->toBeFalse();
    expect(Gate::forUser($user)->allows('update', $other))->toBeFalse();
});

test('un moderador lista y ve usuarios pero no los edita ni impersona', function (): void {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($moderator)->allows('viewAny', User::class))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('view', $other))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('update', $other))->toBeFalse();
    expect(Gate::forUser($moderator)->allows('impersonate', $other))->toBeFalse();
});

test('un admin lista, ve y edita usuarios e impersona a usuarios no staff', function (): void {
    $admin = User::factory()->admin()->create();
    $regular = User::factory()->create();

    expect(Gate::forUser($admin)->allows('viewAny', User::class))->toBeTrue();
    expect(Gate::forUser($admin)->allows('update', $regular))->toBeTrue();
    expect(Gate::forUser($admin)->allows('impersonate', $regular))->toBeTrue();
});

test('un admin no impersona a otro miembro del staff ni a sí mismo', function (): void {
    $admin = User::factory()->admin()->create();
    $otherModerator = User::factory()->moderator()->create();

    expect(Gate::forUser($admin)->allows('impersonate', $otherModerator))->toBeFalse();
    expect(Gate::forUser($admin)->allows('impersonate', $admin))->toBeFalse();
});

test('un admin modera a otros usuarios pero no a sí mismo', function (): void {
    $admin = User::factory()->admin()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($admin)->allows('ban', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('unban', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('changeRole', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('sendPasswordReset', $other))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('ban', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('changeRole', $admin))->toBeFalse();
});

test('un moderador no banea, no cambia roles, no envía resets ni impersona', function (): void {
    $moderator = User::factory()->moderator()->create();
    $other = User::factory()->create();

    expect(Gate::forUser($moderator)->allows('ban', $other))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('changeRole', $other))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('sendPasswordReset', $other))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('impersonate', $other))->toBeFalse();
});

test('un admin no impersona a staff ni a usuarios baneados, ni a sí mismo', function (): void {
    $admin = User::factory()->admin()->create();

    expect(Gate::forUser($admin)->allows('impersonate', User::factory()->moderator()->create()))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('impersonate', User::factory()->admin()->create()))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('impersonate', User::factory()->banned()->create()))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('impersonate', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('impersonate', User::factory()->create()))->toBeTrue();
});

test('solo un admin crea usuarios y gestiona borrados, verificación y reenvíos en otros usuarios', function (): void {
    $moderator = User::factory()->moderator()->create();
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();

    expect(Gate::forUser($moderator)->allows('create', User::class))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('delete', $member))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('restore', $member))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('verifyEmail', $member))->toBeFalse()
        ->and(Gate::forUser($moderator)->allows('resendVerification', $member))->toBeFalse();

    expect(Gate::forUser($admin)->allows('create', User::class))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $member))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('restore', $member))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('verifyEmail', $member))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('resendVerification', $member))->toBeTrue()
        ->and(Gate::forUser($admin)->allows('delete', $admin))->toBeFalse()
        ->and(Gate::forUser($admin)->allows('verifyEmail', $admin))->toBeFalse();
});

test('un usuario borrado no se edita hasta que se restaura', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create();
    $member->delete();

    expect(Gate::forUser($admin)->allows('update', $member))->toBeFalse();
});
