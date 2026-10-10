<?php

declare(strict_types=1);

use App\Livewire\CopypastaForm;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

test('un invitado es redirigido al login', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.edit', $copypasta))->assertRedirect(route('login'));
});

test('el autor ve el formulario de edición de los suyos', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('copypastas.edit', $copypasta))
        ->assertOk();
});

test('un usuario no abre la edición de un copy-pasta ajeno', function (): void {
    $user = User::factory()->create();
    $foreign = Copypasta::factory()->create();

    $this->actingAs($user)
        ->get(route('copypastas.edit', $foreign))
        ->assertNotFound();
});

test('el formulario de edición conserva la ocultación y muestra el motivo', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden('Datos personales')->create(['user_id' => $user->id]);
    $copypasta->tags()->attach(Tag::factory()->create()->getKey());

    Livewire::actingAs($user)
        ->test(CopypastaForm::class, ['copypasta' => $copypasta])
        ->assertSee('Datos personales')
        ->set('title', 'Titulo editado')
        ->call('save')
        ->assertHasNoErrors();

    $copypasta->refresh();

    expect($copypasta->title)->toBe('Titulo editado')
        ->and($copypasta->isHidden())->toBeTrue()
        ->and($copypasta->edited_at)->not->toBeNull();
});

test('editar crea una revisión cuando cambia el texto', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id]);
    $copypasta->tags()->attach(Tag::factory()->create()->getKey());
    $revisionsBefore = $copypasta->revisions()->count();

    Livewire::actingAs($user)
        ->test(CopypastaForm::class, ['copypasta' => $copypasta])
        ->set('body', 'Un cuerpo completamente distinto y suficientemente largo')
        ->call('save');

    expect($copypasta->revisions()->count())->toBeGreaterThan($revisionsBefore);
});

test('el autor elimina su copy-pasta desde la edición', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(CopypastaForm::class, ['copypasta' => $copypasta])
        ->call('delete');

    expect(Copypasta::withTrashed()->whereKey($copypasta->getKey())->first()?->deleted_at)->not->toBeNull();
});
