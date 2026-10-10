<?php

declare(strict_types=1);

use App\Http\Controllers\Public\NsfwConfirmationController;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Str;

function publicFolderWith(array $copypastas, array $attributes = []): Folder
{
    $folder = Folder::factory()->public()->create($attributes);
    $folder->copypastas()->attach(collect($copypastas)->mapWithKeys(fn (Copypasta $copypasta): array => [$copypasta->getKey() => ['created_at' => now()]])->all());

    return $folder;
}

test('la carpeta pública muestra nombre, descripción, autor y tarjetas, y nunca los ocultos o borrados', function (): void {
    $visible = Copypasta::factory()->create(['title' => 'Visible para todos']);
    $hidden = Copypasta::factory()->hidden()->create(['title' => 'Titulo retirado por moderacion']);
    $deleted = Copypasta::factory()->create(['title' => 'Titulo ya borrado']);
    $deleted->delete();
    $folder = publicFolderWith([$visible, $hidden, $deleted], ['name' => 'Mis joyas', 'description' => 'Lo mejor del foro']);

    $this->get(route('folders.public', $folder->public_id))
        ->assertOk()
        ->assertSee('Mis joyas')
        ->assertSee('Lo mejor del foro')
        ->assertSee($folder->user->username)
        ->assertSee('Visible para todos')
        ->assertDontSee('Titulo retirado por moderacion')
        ->assertDontSee('Titulo ya borrado')
        ->assertDontSee(__('app.folders.removed_content'));
});

test('el propio dueño tampoco ve los ocultos en la página pública', function (): void {
    $hidden = Copypasta::factory()->hidden()->create(['title' => 'Titulo retirado por moderacion']);
    $folder = publicFolderWith([$hidden]);

    $this->actingAs($folder->user)
        ->get(route('folders.public', $folder->public_id))
        ->assertOk()
        ->assertDontSee('Titulo retirado por moderacion');
});

test('una carpeta privada, una vuelta a privada y un id desconocido responden 404', function (): void {
    $private = Folder::factory()->create();
    $private->forceFill(['public_id' => (string) Str::ulid()])->save();
    $folder = publicFolderWith([]);

    $this->get(route('folders.public', $private->public_id))->assertNotFound();
    $this->get(route('folders.public', $folder->public_id))->assertOk();

    $folder->forceFill(['is_public' => false])->save();

    $this->get(route('folders.public', $folder->public_id))->assertNotFound();
    $this->get('/col/no-es-un-ulid')->assertNotFound();
});

test('la carpeta de una cuenta baneada, anonimizada o borrada responde 404', function (): void {
    $banned = Folder::factory()->public()->for(User::factory()->banned())->create();
    $anonymized = Folder::factory()->public()->for(User::factory()->create(['anonymized_at' => now()]))->create();
    $deletedOwner = User::factory()->create();
    $deleted = Folder::factory()->public()->for($deletedOwner)->create();
    $deletedOwner->delete();

    foreach ([$banned, $anonymized, $deleted] as $folder) {
        $this->get(route('folders.public', $folder->public_id))->assertNotFound();
    }
});

test('el contenido NSFW respeta la preferencia de quien mira', function (): void {
    $nsfw = Copypasta::factory()->nsfw()->create(['title' => 'Contenido adulto']);
    $folder = publicFolderWith([$nsfw]);
    $url = route('folders.public', $folder->public_id);

    $this->get($url)->assertOk()->assertDontSee('Contenido adulto');

    $this->actingAs(User::factory()->create())->get($url)->assertDontSee('Contenido adulto');

    $this->actingAs(User::factory()->create(['show_nsfw' => true, 'nsfw_confirmed_at' => now()]))
        ->get($url)
        ->assertSee('Contenido adulto');
});

test('un invitado que confirmó la mayoría de edad ve el contenido NSFW', function (): void {
    $folder = publicFolderWith([Copypasta::factory()->nsfw()->create(['title' => 'Contenido adulto'])]);

    $this->withCookie(NsfwConfirmationController::COOKIE, '1')
        ->get(route('folders.public', $folder->public_id))
        ->assertSee('Contenido adulto');
});

test('la página lleva canonical y og:url sin parámetros', function (): void {
    $folder = publicFolderWith([]);
    $url = route('folders.public', $folder->public_id);

    $this->get($url.'?ref=abcdefgh')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$url.'">', false)
        ->assertSee('<meta property="og:url" content="'.$url.'">', false);
});

test('la carpeta pública sale en el perfil de su dueño con el número de copy-pastas visibles y las privadas no', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->public()->for($owner)->create(['name' => 'Pública de prueba']);
    $folder->copypastas()->attach([
        Copypasta::factory()->create()->getKey() => ['created_at' => now()],
        Copypasta::factory()->hidden()->create()->getKey() => ['created_at' => now()],
    ]);
    Folder::factory()->for($owner)->create(['name' => 'Privada de prueba']);

    $this->get(route('profile.show', $owner->username))
        ->assertOk()
        ->assertSee('Pública de prueba')
        ->assertSee(route('folders.public', $folder->public_id), false)
        ->assertSee('1 copy-pasta')
        ->assertDontSee('Privada de prueba');
});
