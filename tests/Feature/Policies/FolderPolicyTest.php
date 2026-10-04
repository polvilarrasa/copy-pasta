<?php

declare(strict_types=1);

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un usuario ve, renombra y borra sus carpetas no predeterminadas', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user, 'user')->create();

    expect(Gate::forUser($user)->allows('view', $folder))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $folder))->toBeTrue();
    expect(Gate::forUser($user)->allows('delete', $folder))->toBeTrue();
});

test('la carpeta Favoritos no se puede renombrar ni borrar', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user, 'user')->create();

    expect(Gate::forUser($user)->allows('view', $favorites))->toBeTrue();
    expect(Gate::forUser($user)->allows('update', $favorites))->toBeFalse();
    expect(Gate::forUser($user)->allows('delete', $favorites))->toBeFalse();
});

test('un usuario no ve ni modifica carpetas ajenas', function (): void {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $folder = Folder::factory()->for($owner, 'user')->create();

    expect(Gate::forUser($other)->allows('view', $folder))->toBeFalse();
    expect(Gate::forUser($other)->allows('update', $folder))->toBeFalse();
    expect(Gate::forUser($other)->allows('delete', $folder))->toBeFalse();
});

test('un moderador no tiene acceso a las carpetas de otros usuarios', function (): void {
    $owner = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $folder = Folder::factory()->for($owner, 'user')->create();

    expect(Gate::forUser($moderator)->allows('view', $folder))->toBeFalse();
});

test('cualquier usuario autenticado puede crear carpetas', function (): void {
    $user = User::factory()->create();

    expect(Gate::forUser($user)->allows('create', Folder::class))->toBeTrue();
});
