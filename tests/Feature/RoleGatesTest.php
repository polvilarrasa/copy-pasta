<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario normal no tiene acceso de staff ni de gestión de usuarios', function (): void {
    $user = User::factory()->create();

    expect($user->isStaff())->toBeFalse();
    expect($user->isAdmin())->toBeFalse();
    expect(Gate::forUser($user)->allows('access-admin'))->toBeFalse();
    expect(Gate::forUser($user)->allows('manage-users'))->toBeFalse();
});

test('un moderador tiene acceso de staff pero no de gestión de usuarios', function (): void {
    $moderator = User::factory()->moderator()->create();

    expect($moderator->isStaff())->toBeTrue();
    expect($moderator->isAdmin())->toBeFalse();
    expect(Gate::forUser($moderator)->allows('access-admin'))->toBeTrue();
    expect(Gate::forUser($moderator)->allows('manage-users'))->toBeFalse();
});

test('un admin tiene acceso de staff y de gestión de usuarios', function (): void {
    $admin = User::factory()->admin()->create();

    expect($admin->isStaff())->toBeTrue();
    expect($admin->isAdmin())->toBeTrue();
    expect(Gate::forUser($admin)->allows('access-admin'))->toBeTrue();
    expect(Gate::forUser($admin)->allows('manage-users'))->toBeTrue();
});
