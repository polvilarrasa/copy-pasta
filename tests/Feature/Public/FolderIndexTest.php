<?php

declare(strict_types=1);

use App\Livewire\FoldersGrid;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Livewire\Livewire;

test('un invitado es redirigido al login', function (): void {
    $this->get('/carpetas')->assertRedirect(route('login'));
});

test('la lista muestra solo las carpetas del usuario', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['name' => 'La mía']);
    Folder::factory()->create(['name' => 'La ajena']);

    $this->actingAs($user)
        ->get('/carpetas')
        ->assertOk()
        ->assertSee('La mía')
        ->assertDontSee('La ajena');
});

test('el usuario crea una carpeta', function (): void {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->set('newFolderName', 'Recetas')
        ->call('createFolder')
        ->assertHasNoErrors();

    expect(Folder::query()->where('user_id', $user->id)->where('name', 'Recetas')->exists())->toBeTrue();
});

test('crear rechaza un nombre repetido', function (): void {
    $user = User::factory()->create();
    Folder::factory()->for($user)->create(['name' => 'Recetas']);

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->set('newFolderName', 'Recetas')
        ->call('createFolder')
        ->assertHasErrors(['name']);
});

test('el usuario renombra una carpeta y le añade descripción', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create(['name' => 'Vieja']);

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->call('startEdit', $folder->getKey())
        ->set('editingName', 'Nueva')
        ->set('editingDescription', 'Las que mando al grupo')
        ->call('saveEdit');

    $folder->refresh();

    expect($folder->name)->toBe('Nueva')
        ->and($folder->description)->toBe('Las que mando al grupo');
});

test('Favoritos admite descripción pero no cambia de nombre', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user)->create();

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->call('startEdit', $favorites->getKey())
        ->set('editingName', 'Intento de renombrar')
        ->set('editingDescription', 'Mis preferidas')
        ->call('saveEdit');

    $favorites->refresh();

    expect($favorites->name)->toBe(Folder::DEFAULT_NAME)
        ->and($favorites->description)->toBe('Mis preferidas');
});

test('el usuario borra una carpeta y sus copy-pastas siguen existiendo', function (): void {
    $user = User::factory()->create();
    $folder = Folder::factory()->for($user)->create();
    $copypasta = Copypasta::factory()->create();
    $folder->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->call('confirmDelete', $folder->getKey())
        ->call('deleteFolder');

    expect(Folder::query()->whereKey($folder->getKey())->exists())->toBeFalse()
        ->and(Copypasta::query()->whereKey($copypasta->getKey())->exists())->toBeTrue();
});

test('Favoritos no se puede borrar', function (): void {
    $user = User::factory()->create();
    $favorites = Folder::factory()->default()->for($user)->create();

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->call('confirmDelete', $favorites->getKey())
        ->call('deleteFolder')
        ->assertForbidden();

    expect(Folder::query()->whereKey($favorites->getKey())->exists())->toBeTrue();
});

test('subir y bajar reordena solo las carpetas propias', function (): void {
    $user = User::factory()->create();
    $first = Folder::factory()->for($user)->create(['position' => 1]);
    $second = Folder::factory()->for($user)->create(['position' => 2]);
    $foreign = Folder::factory()->create(['position' => 7]);

    Livewire::actingAs($user)
        ->test(FoldersGrid::class)
        ->call('moveUp', $second->getKey());

    expect($first->refresh()->position)->toBe(2)
        ->and($second->refresh()->position)->toBe(1)
        ->and($foreign->refresh()->position)->toBe(7);
});
