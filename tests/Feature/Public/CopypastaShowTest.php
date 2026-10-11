<?php

declare(strict_types=1);

use App\Actions\SaveCopypastaRevision;
use App\Models\Copypasta;
use App\Models\User;

test('un visitante recibe 404 al ver un copy-pasta oculto', function (): void {
    $copypasta = Copypasta::factory()->hidden()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))->assertNotFound();
});

test('el autor de un copy-pasta oculto lo ve con el motivo', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->hidden('Spam repetido')->create(['user_id' => $author->id]);

    $this->actingAs($author)
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('Spam repetido');
});

test('el staff ve los copy-pastas ocultos', function (): void {
    $copypasta = Copypasta::factory()->hidden()->create();

    $this->actingAs(User::factory()->moderator()->create())
        ->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk();
});

test('si el slug no coincide responde con 301 al slug actual', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Titulo real']);

    $this->get('/c/'.$copypasta->id.'/otro-slug')
        ->assertStatus(301)
        ->assertRedirect(route('copypastas.show', [$copypasta, $copypasta->slug]));
});

test('incluye metaetiquetas Open Graph y Twitter con los primeros 200 caracteres', function (): void {
    $body = str_repeat('a', 250);
    $copypasta = Copypasta::factory()->create(['title' => 'Titulo para compartir', 'body' => $body]);

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('property="og:title" content="Titulo para compartir"', false)
        ->assertSee('name="twitter:card" content="summary_large_image"', false)
        ->assertSee('property="og:description" content="'.str_repeat('a', 200).'"', false)
        ->assertDontSee('content="'.str_repeat('a', 201).'"', false);
});

test('un copy-pasta NSFW usa una descripción genérica en la vista previa', function (): void {
    $copypasta = Copypasta::factory()->nsfw()->create(['body' => 'Texto que no debe filtrarse']);

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('property="og:description" content="'.__('public.og.nsfw_description').'"', false)
        ->assertDontSee('og:description" content="Texto que no debe filtrarse', false);
});

test('un visitante no ve un copy-pasta sin publicar', function (): void {
    $copypasta = Copypasta::factory()->unpublished()->create();

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))->assertNotFound();
});

test('el detalle de un copy-pasta editado muestra las versiones anteriores', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Titulo viejo', 'body' => 'Cuerpo viejo']);
    app(SaveCopypastaRevision::class)->handle($copypasta);
    $copypasta->forceFill(['title' => 'Titulo nuevo', 'body' => 'Cuerpo nuevo', 'edited_at' => now()])->save();
    app(SaveCopypastaRevision::class)->handle($copypasta);

    $this->get(route('copypastas.show', [$copypasta, $copypasta->slug]))
        ->assertOk()
        ->assertSee('Titulo viejo')
        ->assertSee('Cuerpo viejo');
});
