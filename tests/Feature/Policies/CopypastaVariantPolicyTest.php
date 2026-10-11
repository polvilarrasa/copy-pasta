<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('un miembro verificado puede crear una variante de un copy-pasta visible ajeno', function (): void {
    $parent = Copypasta::factory()->create();

    expect(Gate::forUser(User::factory()->create())->allows('createVariant', $parent))->toBeTrue();
});

test('el autor no puede crear una variante de su propio copy-pasta', function (): void {
    $author = User::factory()->create();
    $parent = Copypasta::factory()->for($author, 'user')->create();

    expect(Gate::forUser($author)->allows('createVariant', $parent))->toBeFalse();
});

test('sin email verificado no se crea una variante', function (): void {
    $parent = Copypasta::factory()->create();

    expect(Gate::forUser(User::factory()->unverified()->create())->allows('createVariant', $parent))->toBeFalse();
});

test('no se crea una variante de un copy-pasta oculto, borrado o sin publicar, ni siquiera el staff', function (string $state): void {
    $parent = match ($state) {
        'oculto' => Copypasta::factory()->hidden()->create(),
        'borrado' => tap(Copypasta::factory()->create())->delete(),
        'sin publicar' => Copypasta::factory()->unpublished()->create(),
    };

    foreach ([User::factory()->create(), User::factory()->moderator()->create(), User::factory()->admin()->create()] as $user) {
        expect(Gate::forUser($user)->allows('createVariant', $parent))->toBeFalse();
    }
})->with(['oculto', 'borrado', 'sin publicar']);

test('el staff verificado puede crear variantes de copy-pastas visibles ajenos', function (): void {
    $parent = Copypasta::factory()->create();

    expect(Gate::forUser(User::factory()->moderator()->create())->allows('createVariant', $parent))->toBeTrue()
        ->and(Gate::forUser(User::factory()->admin()->create())->allows('createVariant', $parent))->toBeTrue();
});
