<?php

declare(strict_types=1);

use App\Actions\PublishCopypasta;
use App\Actions\UpdateCopypasta;
use App\Enums\AchievementMetric;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Validation\ValidationException;

function variantData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Mi variante del clásico',
        'body' => 'Una versión distinta del texto original, con cambios.',
        'is_nsfw' => false,
        'is_template' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ], $overrides);
}

test('publica una variante con el parent_id del padre directo', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'El texto original de siempre']);
    $author = User::factory()->create();

    $variant = app(PublishCopypasta::class)->handle($author, variantData(), $parent);

    expect($variant->refresh()->parent_id)->toBe($parent->getKey())
        ->and($variant->user_id)->toBe($author->getKey())
        ->and($variant->published_at)->not->toBeNull();
});

test('una variante puede tener variantes y apunta a su padre directo', function (): void {
    $root = Copypasta::factory()->create(['body' => 'La raíz del árbol de variantes']);
    $child = app(PublishCopypasta::class)->handle(User::factory()->create(), variantData(['body' => 'Hija de la raíz, distinta']), $root);

    $grandchild = app(PublishCopypasta::class)->handle(User::factory()->create(), variantData(['body' => 'Nieta de la raíz, también distinta']), $child);

    expect($grandchild->refresh()->parent_id)->toBe($child->getKey());
});

test('no se puede crear una variante propia', function (): void {
    $author = User::factory()->create();
    $own = Copypasta::factory()->for($author, 'user')->create();

    app(PublishCopypasta::class)->handle($author, variantData(), $own);
})->throws(AuthorizationException::class);

test('no se puede crear una variante de un copy-pasta oculto o borrado', function (string $state): void {
    $parent = $state === 'oculto' ? Copypasta::factory()->hidden()->create() : tap(Copypasta::factory()->create())->delete();

    expect(fn () => app(PublishCopypasta::class)->handle(User::factory()->create(), variantData(), $parent))
        ->toThrow(AuthorizationException::class);

    expect(Copypasta::query()->whereNotNull('parent_id')->count())->toBe(0);
})->with(['oculto', 'borrado']);

test('sin email verificado no se publica una variante', function (): void {
    app(PublishCopypasta::class)->handle(User::factory()->unverified()->create(), variantData(), Copypasta::factory()->create());
})->throws(AuthorizationException::class);

test('una variante idéntica al original una vez normalizada se rechaza', function (string $body): void {
    $parent = Copypasta::factory()->create(['body' => 'Hola   Mundo,  esto es el ORIGINAL']);
    $author = User::factory()->create();

    expect(fn () => app(PublishCopypasta::class)->handle($author, variantData(['body' => $body]), $parent))
        ->toThrow(ValidationException::class, __('app.errors.variant_unchanged'));

    expect(Copypasta::query()->count())->toBe(1);
})->with([
    'igual' => 'Hola   Mundo,  esto es el ORIGINAL',
    'mayúsculas y espacios' => "  hola mundo, esto es el\noriginal  ",
]);

test('una variante con otro título pero el mismo cuerpo se rechaza', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'El cuerpo que no cambia']);

    expect(fn () => app(PublishCopypasta::class)->handle(User::factory()->create(), variantData(['body' => 'El cuerpo que no cambia', 'title' => 'Otro título distinto']), $parent))
        ->toThrow(ValidationException::class);
});

test('publicar una variante de otra persona suma a su progreso de variantes', function (): void {
    $author = User::factory()->create();

    app(PublishCopypasta::class)->handle($author, variantData(), Copypasta::factory()->create(['body' => 'Original uno']));

    expect(achievementProgress($author, AchievementMetric::VariantsPublished))->toBe(1)
        ->and(achievementProgress($author, AchievementMetric::Published))->toBe(1);
});

test('publicar un copy-pasta normal no suma a variantes', function (): void {
    $author = User::factory()->create();

    app(PublishCopypasta::class)->handle($author, variantData());

    expect(achievementProgress($author, AchievementMetric::VariantsPublished))->toBe(0);
});

test('editar una variante ignora un parent_id que llegue en los datos', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'Original de verdad']);
    $other = Copypasta::factory()->create(['body' => 'Otro copy-pasta cualquiera']);
    $author = User::factory()->create();
    $variant = app(PublishCopypasta::class)->handle($author, variantData(), $parent);

    app(UpdateCopypasta::class)->handle($author, $variant, [...variantData(['body' => 'Variante editada y distinta']), 'parent_id' => $other->getKey()]);
    expect($variant->refresh()->parent_id)->toBe($parent->getKey());

    app(UpdateCopypasta::class)->handle($author, $variant, [...variantData(['body' => 'Variante editada otra vez']), 'parent_id' => null]);
    expect($variant->refresh()->parent_id)->toBe($parent->getKey());
});

test('editar una variante hasta igualar el cuerpo de su padre se rechaza', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'Cuerpo del padre']);
    $author = User::factory()->create();
    $variant = app(PublishCopypasta::class)->handle($author, variantData(), $parent);

    expect(fn () => app(UpdateCopypasta::class)->handle($author, $variant, variantData(['body' => '  cuerpo   del PADRE'])))
        ->toThrow(ValidationException::class);
});

test('editar una variante cuando el padre ya no está visible sigue permitido si cambia el cuerpo', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'Cuerpo del padre']);
    $author = User::factory()->create();
    $variant = app(PublishCopypasta::class)->handle($author, variantData(), $parent);
    $parent->delete();

    app(UpdateCopypasta::class)->handle($author, $variant, variantData(['body' => 'Cuerpo nuevo de la variante']));

    expect($variant->refresh()->body)->toBe('Cuerpo nuevo de la variante');
});
