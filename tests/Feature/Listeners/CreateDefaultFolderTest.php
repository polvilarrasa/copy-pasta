<?php

declare(strict_types=1);

use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Events\Registered;

test('al registrarse un usuario recibe la carpeta Favoritos por defecto', function (): void {
    $user = User::factory()->create();

    event(new Registered($user));

    $folder = Folder::query()->where('user_id', $user->id)->where('is_default', true)->first();

    expect($folder)->not->toBeNull()
        ->and($folder->name)->toBe('Favoritos');
});

test('disparar el registro dos veces no duplica la carpeta Favoritos', function (): void {
    $user = User::factory()->create();

    event(new Registered($user));
    event(new Registered($user));

    expect(Folder::query()->where('user_id', $user->id)->where('is_default', true)->count())->toBe(1);
});
