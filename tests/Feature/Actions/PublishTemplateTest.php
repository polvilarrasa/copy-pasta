<?php

declare(strict_types=1);

use App\Actions\PublishCopypasta;
use App\Actions\UpdateCopypasta;
use App\Livewire\CopypastaForm;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

function templateData(array $overrides = []): array
{
    return array_merge([
        'title' => 'Titulo de prueba',
        'body' => 'Hola {{nombre}}, escribo desde {{ciudad}}.',
        'is_nsfw' => false,
        'is_template' => true,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ], $overrides);
}

test('publica una plantilla con variables válidas', function (): void {
    $copypasta = app(PublishCopypasta::class)->handle(User::factory()->create(), templateData());

    expect($copypasta->refresh()->is_template)->toBeTrue();
});

test('una plantilla sin ninguna variable válida no se publica', function (string $body): void {
    $author = User::factory()->create();

    expect(fn () => app(PublishCopypasta::class)->handle($author, templateData(['body' => $body])))
        ->toThrow(ValidationException::class);

    expect(Copypasta::query()->count())->toBe(0);
})->with([
    'sin llaves' => 'Un texto sin variables de ningún tipo.',
    'nombre de 31 caracteres' => '{{'.str_repeat('a', 31).'}} y nada más',
    'llaves vacías' => 'Texto con {{}} vacías y {{a-b}} inválidas',
]);

test('una plantilla con más de 10 variables distintas no se publica y con 10 sí', function (): void {
    $author = User::factory()->create();
    $tokens = fn (int $count): string => collect(range(1, $count))->map(fn (int $n): string => '{{v'.$n.'}}')->implode(' ');

    expect(fn () => app(PublishCopypasta::class)->handle($author, templateData(['body' => $tokens(11)])))
        ->toThrow(ValidationException::class);

    expect(app(PublishCopypasta::class)->handle($author, templateData(['body' => $tokens(10)]))->is_template)->toBeTrue();
});

test('las variables que solo cambian de mayúsculas cuentan como una para el límite', function (): void {
    $body = collect(range(1, 10))->map(fn (int $n): string => '{{v'.$n.'}}')->implode(' ').' {{V1}} {{V2}}';

    $copypasta = app(PublishCopypasta::class)->handle(User::factory()->create(), templateData(['body' => $body]));

    expect($copypasta->is_template)->toBeTrue();
});

test('sin la casilla, las llaves son texto normal aunque no haya variables válidas', function (): void {
    $copypasta = app(PublishCopypasta::class)->handle(
        User::factory()->create(),
        templateData(['is_template' => false, 'body' => 'Esto {{no es}} una plantilla, solo texto con llaves.']),
    );

    expect($copypasta->refresh()->is_template)->toBeFalse();
});

test('editar valida la plantilla en la Action y permite marcarla o desmarcarla', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['body' => 'Un texto largo sin variables.']);
    $data = templateData(['body' => 'Un texto largo sin variables.', 'tag_ids' => [Tag::factory()->create()->getKey()]]);

    expect(fn () => app(UpdateCopypasta::class)->handle($author, $copypasta, $data))->toThrow(ValidationException::class);

    app(UpdateCopypasta::class)->handle($author, $copypasta, [...$data, 'body' => 'Hola {{nombre}}, qué tal.']);
    expect($copypasta->refresh()->is_template)->toBeTrue();

    app(UpdateCopypasta::class)->handle($author, $copypasta, [...$data, 'body' => 'Hola {{nombre}}, qué tal.', 'is_template' => false]);
    expect($copypasta->refresh()->is_template)->toBeFalse();
});

test('editar una plantilla hasta dejarla sin variables falla', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->template()->for($author, 'user')->create();

    expect(fn () => app(UpdateCopypasta::class)->handle($author, $copypasta, templateData(['body' => 'Ya no queda ninguna variable aquí.'])))
        ->toThrow(ValidationException::class);
});

test('el formulario muestra en vivo las variables detectadas y el error de plantilla sin variables', function (): void {
    Tag::factory()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class)
        ->set('body', 'Hola {{ Nombre }} y {{nombre}} y {{ciudad}}')
        ->assertSet('templateTokens', ['{{Nombre}}', '{{ciudad}}'])
        ->set('title', 'Un título válido')
        ->set('isTemplate', true)
        ->set('body', 'Un cuerpo sin variables para publicar')
        ->set('tagIds', [Tag::query()->first()->getKey()])
        ->call('save')
        ->assertHasErrors(['is_template']);
});
