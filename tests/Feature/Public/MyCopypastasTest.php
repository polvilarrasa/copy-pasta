<?php

declare(strict_types=1);

use App\Livewire\MyCopypastas;
use App\Models\Copypasta;
use App\Models\User;
use Livewire\Livewire;

test('un invitado es redirigido al login', function (): void {
    $this->get('/mis-copypastas')->assertRedirect(route('login'));
});

test('un usuario sin email verificado también ve su lista', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/mis-copypastas')
        ->assertOk();
});

test('un miembro baneado no accede aunque tenga sesión', function (): void {
    $this->actingAs(User::factory()->banned()->create())
        ->get('/mis-copypastas')
        ->assertRedirect(route('login'));
});

test('la lista muestra solo los copy-pastas del usuario', function (): void {
    $user = User::factory()->create();
    $own = Copypasta::factory()->create(['user_id' => $user->id, 'title' => 'El mío']);
    Copypasta::factory()->create(['title' => 'El ajeno']);

    $this->actingAs($user)
        ->get('/mis-copypastas')
        ->assertOk()
        ->assertSee('El mío')
        ->assertDontSee('El ajeno');
});

test('el listado indica el estado oculto con su motivo', function (): void {
    $user = User::factory()->create();
    Copypasta::factory()->hidden('Spam repetido')->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get('/mis-copypastas')
        ->assertOk()
        ->assertSee(__('app.status.hidden'))
        ->assertSee('Spam repetido');
});

test('el autor borra su copy-pasta con borrado lógico', function (): void {
    $user = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['user_id' => $user->id]);

    Livewire::actingAs($user)
        ->test(MyCopypastas::class)
        ->call('confirmDelete', $copypasta->getKey())
        ->call('delete');

    expect(Copypasta::withTrashed()->whereKey($copypasta->getKey())->first()?->deleted_at)->not->toBeNull();
});

test('un miembro no puede borrar el copy-pasta de otro', function (): void {
    $user = User::factory()->create();
    $foreign = Copypasta::factory()->create();

    Livewire::actingAs($user)
        ->test(MyCopypastas::class)
        ->call('confirmDelete', $foreign->getKey())
        ->call('delete')
        ->assertForbidden();

    expect(Copypasta::query()->whereKey($foreign->getKey())->exists())->toBeTrue();
});
