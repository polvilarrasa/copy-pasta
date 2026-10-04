<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;

test('un anónimo es redirigido al login al pedir o cambiar carpetas', function (): void {
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.folders.index', $copypasta))->assertRedirect(route('login'));
    $this->putJson(route('copypastas.folders.sync', $copypasta), ['folder_ids' => []])->assertUnauthorized();
});

test('el selector lista las carpetas del miembro y marca las que contienen el copy-pasta', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $recipes = Folder::factory()->for($member)->create(['name' => 'Recetas']);
    Folder::factory()->for($member)->create(['name' => 'Otra']);
    $recipes->copypastas()->attach($copypasta->getKey(), ['created_at' => now()]);

    $response = $this->actingAs($member)->getJson(route('copypastas.folders.index', $copypasta))->assertOk();

    expect(collect($response->json('folders'))->keyBy('name')->map->contains->all())
        ->toBe(['Favoritos' => false, 'Otra' => false, 'Recetas' => true]);
});

test('el selector no lista carpetas de otros miembros', function (): void {
    $member = User::factory()->create();
    Folder::factory()->create(['name' => 'Ajena']);

    $response = $this->actingAs($member)->getJson(route('copypastas.folders.index', Copypasta::factory()->create()))->assertOk();

    expect(collect($response->json('folders'))->pluck('name')->all())->not->toContain('Ajena');
});

test('guardar el selector deja el copy-pasta en las carpetas marcadas y actualiza favoritos', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create(['favorites_count' => 0]);
    $favorites = Folder::ensureDefaultFor($member);
    $recipes = Folder::factory()->for($member)->create();

    $this->actingAs($member)
        ->putJson(route('copypastas.folders.sync', $copypasta), ['folder_ids' => [$favorites->getKey(), $recipes->getKey()]])
        ->assertOk()
        ->assertJson(['favorited' => true, 'favorites_count' => 1]);

    $this->actingAs($member)
        ->putJson(route('copypastas.folders.sync', $copypasta), ['folder_ids' => [$recipes->getKey()]])
        ->assertOk()
        ->assertJson(['favorited' => false, 'favorites_count' => 0]);

    expect($copypasta->folders()->pluck('folders.id')->all())->toBe([$recipes->getKey()]);
});

test('guardar rechaza carpetas que no son del miembro', function (): void {
    $member = User::factory()->create();
    $foreign = Folder::factory()->create();

    $this->actingAs($member)
        ->putJson(route('copypastas.folders.sync', Copypasta::factory()->create()), ['folder_ids' => [$foreign->getKey()]])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['folder_ids.0']);
});

test('no se puede añadir a carpetas un copy-pasta oculto', function (): void {
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create();

    $this->actingAs($member)
        ->putJson(route('copypastas.folders.sync', Copypasta::factory()->hidden()->create()), ['folder_ids' => [$folder->getKey()]])
        ->assertForbidden();
});

test('crear una carpeta desde el selector la crea y añade el copy-pasta', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    $this->actingAs($member)
        ->postJson(route('copypastas.folders.store', $copypasta), ['name' => 'Trucos'])
        ->assertCreated()
        ->assertJsonPath('folder.name', 'Trucos')
        ->assertJsonPath('folder.contains', true);

    expect($copypasta->folders()->where('folders.user_id', $member->getKey())->pluck('name')->all())->toBe(['Trucos']);
});

test('el selector rechaza un nombre repetido o vacío', function (): void {
    $member = User::factory()->create();
    Folder::factory()->for($member)->create(['name' => 'Trucos']);
    $copypasta = Copypasta::factory()->create();

    $this->actingAs($member)
        ->postJson(route('copypastas.folders.store', $copypasta), ['name' => 'Trucos'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);

    $this->actingAs($member)
        ->postJson(route('copypastas.folders.store', $copypasta), ['name' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});

test('el selector no crea carpetas más allá del límite', function (): void {
    $member = User::factory()->create();
    Folder::factory()->for($member)->count(Folder::MAX_PER_USER)->create();

    $this->actingAs($member)
        ->postJson(route('copypastas.folders.store', Copypasta::factory()->create()), ['name' => 'Una más'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name']);
});
