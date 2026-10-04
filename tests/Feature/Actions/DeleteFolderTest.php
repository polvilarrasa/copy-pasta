<?php

declare(strict_types=1);

use App\Actions\DeleteFolder;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('el dueño borra una carpeta y conserva los copy-pastas', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(DeleteFolder::class)->handle($user, $folder);

    expect(Folder::query()->whereKey($folder->getKey())->exists())->toBeFalse()
        ->and(Copypasta::query()->whereKey($copypasta->getKey())->exists())->toBeTrue();
});

test('la carpeta Favoritos no se puede borrar', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user)->create();

    app(DeleteFolder::class)->handle($user, $favorites);
})->throws(AuthorizationException::class);

test('no se borra una carpeta ajena', function (): void {
    $folder = Folder::factory()->create();

    app(DeleteFolder::class)->handle(User::factory()->create(), $folder);
})->throws(AuthorizationException::class);
