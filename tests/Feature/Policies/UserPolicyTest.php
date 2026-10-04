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
