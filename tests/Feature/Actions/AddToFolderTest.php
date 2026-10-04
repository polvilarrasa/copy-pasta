<?php

declare(strict_types=1);

use App\Actions\AddToFolder;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

test('un copy-pasta puede estar en varias carpetas a la vez', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $recipes = Folder::factory()->for($user)->create();
    $tricks = Folder::factory()->for($user)->create();

    app(AddToFolder::class)->handle($user, $recipes, $copypasta);
    app(AddToFolder::class)->handle($user, $tricks, $copypasta);

    expect($copypasta->folders()->pluck('folders.id')->sort()->values()->all())
        ->toBe(collect([$recipes->getKey(), $tricks->getKey()])->sort()->values()->all());
});

test('añadir dos veces a la misma carpeta no duplica la entrada', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $folder = Folder::factory()->for($user)->create();

    expect(app(AddToFolder::class)->handle($user, $folder, $copypasta))->toBeTrue()
        ->and(app(AddToFolder::class)->handle($user, $folder, $copypasta))->toBeFalse()
        ->and($copypasta->folders()->count())->toBe(1);
});

test('añadir a Favoritos sube el contador de favoritos', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $favorites = Folder::factory()->default()->for($user)->create();

    app(AddToFolder::class)->handle($user, $favorites, $copypasta);

    expect($copypasta->refresh()->favorites_count)->toBe(1);
});

test('añadir a una carpeta normal no toca el contador de favoritos', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);

    app(AddToFolder::class)->handle($user, Folder::factory()->for($user)->create(), $copypasta);

    expect($copypasta->refresh()->favorites_count)->toBe(0);
});

test('no se añade a una carpeta ajena', function (): void {
    $folder = Folder::factory()->create();

    app(AddToFolder::class)->handle(User::factory()->create(), $folder, Copypasta::factory()->create());
})->throws(AuthorizationException::class);

test('no se añade un copy-pasta oculto', function (): void {
    $user = User::factory()->create();

    app(AddToFolder::class)->handle($user, Folder::factory()->for($user)->create(), Copypasta::factory()->hidden()->create());
})->throws(AuthorizationException::class);
