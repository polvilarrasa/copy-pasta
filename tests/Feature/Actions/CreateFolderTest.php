<?php

declare(strict_types=1);

use App\Actions\CreateFolder;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Validation\ValidationException;

test('la carpeta nueva va al final de la lista del usuario', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['position' => 4]);

    $folder = app(CreateFolder::class)->handle($user, '  Recetas  ');

    expect($folder->name)->toBe('Recetas')
        ->and($folder->position)->toBe(5)
        ->and($folder->is_default)->toBeFalse();
});

test('un usuario no puede pasar de cincuenta carpetas', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->count(Folder::MAX_PER_USER)->create();

    app(CreateFolder::class)->handle($user, 'Una más');
})->throws(ValidationException::class);

test('no se crean dos carpetas con el mismo nombre para el mismo usuario', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['name' => 'Trucos']);

    app(CreateFolder::class)->handle($user, 'Trucos');
})->throws(ValidationException::class);

test('otro usuario sí puede usar el mismo nombre de carpeta', function (): void {
    Folder::factory()->create(['name' => 'Trucos']);

    expect(app(CreateFolder::class)->handle(User::factory()->create(), 'Trucos')->name)->toBe('Trucos');
});
