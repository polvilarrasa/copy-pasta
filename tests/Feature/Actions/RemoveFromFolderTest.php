<?php

declare(strict_types=1);

use App\Actions\RemoveFromFolder;
use App\Actions\SyncCopypastaFolders;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('quitar de una carpeta conserva el copy-pasta en las demás', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $recipes = Folder::factory()->for($user)->create();
    $tricks = Folder::factory()->for($user)->create();
    $recipes->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
    $tricks->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(RemoveFromFolder::class)->handle($user, $recipes, $copypasta);

    expect($copypasta->folders()->pluck('folders.id')->all())->toBe([$tricks->getKey()]);
});

test('quitar de Favoritos baja el contador sin bajar de cero', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $favorites = Folder::factory()->default()->for($user)->create();
    $favorites->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(RemoveFromFolder::class)->handle($user, $favorites, $copypasta);

    expect($copypasta->refresh()->favorites_count)->toBe(0);
});

test('quitar un copy-pasta que no está en la carpeta no hace nada', function (): void {
    $user = User::factory()->create();

    expect(app(RemoveFromFolder::class)->handle($user, Folder::factory()->for($user)->create(), Copypasta::factory()->create()))
        ->toBeFalse();
});

test('no se quita de una carpeta ajena', function (): void {
    $folder = Folder::factory()->create();

    app(RemoveFromFolder::class)->handle(User::factory()->create(), $folder, Copypasta::factory()->create());
})->throws(AuthorizationException::class);

test('sincronizar deja solo las carpetas indicadas y no toca las ajenas', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $keep = Folder::factory()->for($user)->create();
    $drop = Folder::factory()->for($user)->create();
    $add = Folder::factory()->for($user)->create();
    $foreign = Folder::factory()->create();
    $keep->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);
    $drop->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    app(SyncCopypastaFolders::class)->handle($user, $copypasta, [$keep->getKey(), $add->getKey(), $foreign->getKey()]);

    expect($copypasta->folders()->pluck('folders.id')->sort()->values()->all())
        ->toBe(collect([$keep->getKey(), $add->getKey()])->sort()->values()->all())
        ->and($foreign->copypastas()->count())->toBe(0);
});
