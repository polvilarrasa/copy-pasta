<?php

declare(strict_types=1);

use App\Actions\RenameFolder;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

test('el dueño renombra una carpeta no predeterminada', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create(['name' => 'Vieja']);

    app(RenameFolder::class)->handle($user, $folder, 'Nueva');

    expect($folder->refresh()->name)->toBe('Nueva');
});

test('la carpeta Favoritos no se puede renombrar', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user)->create();

    app(RenameFolder::class)->handle($user, $favorites, 'Guardados');
})->throws(AuthorizationException::class);

test('no se renombra una carpeta ajena', function (): void {
    $folder = Folder::factory()->create(['name' => 'Ajena']);

    app(RenameFolder::class)->handle(User::factory()->create(), $folder, 'Mía');
})->throws(AuthorizationException::class);

test('no se renombra a un nombre que ya usa otra carpeta del mismo usuario', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['name' => 'Recetas']);
    $folder = Folder::factory()->for($user)->create(['name' => 'Otra']);

    app(RenameFolder::class)->handle($user, $folder, 'Recetas');
})->throws(ValidationException::class);

test('conservar el mismo nombre no cuenta como duplicado', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create(['name' => 'Recetas']);

    expect(app(RenameFolder::class)->handle($user, $folder, 'Recetas')->name)->toBe('Recetas');
});
