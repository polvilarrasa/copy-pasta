<?php

declare(strict_types=1);

use App\Livewire\CopypastaForm;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

/**
 * @return array{title: string, body: string, tagIds: array<int, int>, isNsfw: bool}
 */
function validCopypastaFormState(array $overrides = []): array
{
    return array_merge([
        'title' => 'Titulo válido',
        'body' => 'Cuerpo válido con suficiente texto',
        'tagIds' => [Tag::factory()->create()->getKey()],
        'isNsfw' => false,
    ], $overrides);
}

test('un invitado es redirigido al login', function (): void {
    $this->get('/publicar')->assertRedirect(route('login'));
});

test('un usuario sin email verificado ve un aviso para verificar, no el formulario', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/publicar')
        ->assertOk()
        ->assertSee(__('public.publish.unverified_title'))
        ->assertDontSee(__('app.fields.tags'));
});

test('un miembro verificado ve el formulario de publicación', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/publicar')
        ->assertOk()
        ->assertSee(__('public.publish.submit_create'));
});

test('el botón publicar de la web pública siempre lleva a /publicar para cualquier autenticado', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('href="'.route('copypastas.create').'"', false);

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('home'))
        ->assertSee('href="'.route('copypastas.create').'"', false);
});

test('un miembro crea un copy-pasta con etiquetas y NSFW', function (): void {
    $user = User::factory()->create();
    $tag = Tag::factory()->create();

    Livewire::actingAs($user)
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['tagIds' => [$tag->getKey()], 'isNsfw' => true]))
        ->call('save')
        ->assertHasNoErrors();

    $copypasta = Copypasta::query()->where('title', 'Titulo válido')->firstOrFail();

    expect($copypasta->user_id)->toBe($user->id)
        ->and($copypasta->is_nsfw)->toBeTrue()
        ->and($copypasta->tags->pluck('id')->all())->toBe([$tag->id]);
});

test('valida la longitud del título y del cuerpo', function (): void {
    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['title' => 'abc', 'body' => 'corto']))
        ->call('save')
        ->assertHasErrors(['title', 'body']);
});

test('valida que haya entre una y cinco etiquetas', function (): void {
    $tags = Tag::factory()->count(6)->create()->pluck('id')->all();

    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['tagIds' => $tags]))
        ->call('save')
        ->assertHasErrors(['tag_ids']);

    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['tagIds' => []]))
        ->call('save')
        ->assertHasErrors(['tag_ids']);
});

test('no ofrece etiquetas desactivadas en el formulario', function (): void {
    $inactive = Tag::factory()->inactive()->create();

    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['tagIds' => [$inactive->getKey()]]))
        ->call('save')
        ->assertHasErrors(['tag_ids']);
});

test('avisa con enlace al existente cuando el cuerpo es duplicado, sin bloquear', function (): void {
    $existing = Copypasta::factory()->create(['body' => 'Cuerpo repetido en el feed']);
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(CopypastaForm::class)
        ->set(validCopypastaFormState(['body' => 'cuerpo   REPETIDO en el feed']))
        ->assertSee(__('app.duplicate.title'))
        ->call('save')
        ->assertHasNoErrors();

    expect(Copypasta::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Copypasta::query()->whereKey($existing->getKey())->exists())->toBeTrue();
});
