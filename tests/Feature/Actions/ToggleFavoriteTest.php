<?php

declare(strict_types=1);

use App\Actions\ToggleFavorite;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;

test('añadir y quitar de favoritos alterna la entrada y el contador', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $toggle = app(ToggleFavorite::class);

    expect($toggle->handle($user, $copypasta))->toBeTrue();

    $copypasta->refresh();
    expect($copypasta->favorites_count)->toBe(1)
        ->and(Folder::query()->where('user_id', $user->id)->where('is_default', true)->first()
            ->copypastas()->whereKey($copypasta->id)->exists())->toBeTrue();

    expect($toggle->handle($user, $copypasta))->toBeFalse();
    expect($copypasta->refresh()->favorites_count)->toBe(0);
});

test('crea la carpeta Favoritos si el usuario no la tiene', function (): void {
    $user = User::factory()->create();
    Folder::query()->where('user_id', $user->id)->delete();

    app(ToggleFavorite::class)->handle($user, Copypasta::factory()->create());

    expect(Folder::query()->where('user_id', $user->id)->where('is_default', true)->where('name', 'Favoritos')->exists())
        ->toBeTrue();
});

test('un copy-pasta puede estar en favoritos y en otras carpetas a la vez', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $other = Folder::factory()->create(['user_id' => $user->id, 'name' => 'Otra', 'is_default' => false]);

    app(ToggleFavorite::class)->handle($user, $copypasta);
    $other->copypastas()->attach($copypasta->id, ['created_at' => now()]);

    expect($copypasta->folders()->count())->toBe(2);
});

test('el contador no baja de cero aunque el registro esté desfasado', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $favorites = app(ToggleFavorite::class);

    $favorites->handle($user, $copypasta);
    Copypasta::query()->whereKey($copypasta->id)->update(['favorites_count' => 0]);

    $favorites->handle($user, $copypasta);

    expect($copypasta->refresh()->favorites_count)->toBe(0);
});
