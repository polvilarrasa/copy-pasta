<?php

declare(strict_types=1);

use App\Actions\HideCopypasta;
use App\Livewire\CopypastaForm;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\User;
use Livewire\Livewire;

// /c/{ulid}/variante

test('un invitado es redirigido al login', function (): void {
    $this->get(route('copypastas.variant', Copypasta::factory()->create()))->assertRedirect(route('login'));
});

test('un miembro verificado ve el formulario prellenado con el original', function (): void {
    $parent = Copypasta::factory()->template()->nsfw()->create(['title' => 'Título del original']);
    $parent->tags()->attach(Tag::factory()->create(['name' => 'humor']));

    $this->actingAs(User::factory()->create())
        ->get(route('copypastas.variant', $parent))
        ->assertOk()
        ->assertSeeLivewire(CopypastaForm::class)
        ->assertSee('Título del original');
});

test('el formulario parte del original: título, cuerpo, etiquetas, NSFW y plantilla', function (): void {
    $parent = Copypasta::factory()->template()->nsfw()->create(['title' => 'Título del original']);
    $tag = Tag::factory()->create();
    $parent->tags()->attach($tag);

    Livewire::actingAs(User::factory()->create())
        ->test(CopypastaForm::class, ['parent' => $parent])
        ->assertSet('title', 'Título del original')
        ->assertSet('body', $parent->body)
        ->assertSet('tagIds', [$tag->getKey()])
        ->assertSet('isNsfw', true)
        ->assertSet('isTemplate', true)
        ->assertSet('duplicate', null);
});

test('guardar sin cambiar el cuerpo muestra el error y guardar con cambios publica la variante', function (): void {
    $parent = Copypasta::factory()->create(['body' => 'Cuerpo original del copy-pasta']);
    $parent->tags()->attach(Tag::factory()->create());
    $member = User::factory()->create();

    $form = Livewire::actingAs($member)->test(CopypastaForm::class, ['parent' => $parent]);

    $form->call('save')->assertHasErrors(['body']);
    expect(Copypasta::query()->whereNotNull('parent_id')->count())->toBe(0);

    $form->set('body', 'Cuerpo cambiado por la variante')->call('save')->assertHasNoErrors();

    $variant = Copypasta::query()->whereNotNull('parent_id')->sole();

    expect($variant->parent_id)->toBe($parent->getKey())->and($variant->user_id)->toBe($member->getKey());
});

test('no hay página de variante para un copy-pasta propio ni oculto', function (): void {
    $member = User::factory()->create();

    $this->actingAs($member)->get(route('copypastas.variant', Copypasta::factory()->for($member, 'user')->create()))->assertNotFound();
    $this->actingAs($member)->get(route('copypastas.variant', Copypasta::factory()->hidden()->create()))->assertNotFound();
});

test('sin email verificado la página pide verificar en vez de mostrar el formulario', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('copypastas.variant', Copypasta::factory()->create()))
        ->assertOk()
        ->assertSee(__('public.variants.unverified_title'))
        ->assertDontSeeLivewire(CopypastaForm::class);
});

test('la ruta de variante no la captura el detalle con slug', function (): void {
    $parent = Copypasta::factory()->create();

    $this->actingAs(User::factory()->create())->get('/c/'.$parent->getKey().'/variante')->assertSeeLivewire(CopypastaForm::class);
});

// Detalle: "Variante de…", pestañas y botón

test('el detalle de una variante enlaza al padre', function (): void {
    $parent = Copypasta::factory()->create(['title' => 'Título del padre visible']);
    $variant = Copypasta::factory()->variantOf($parent)->create();

    $this->get(route('copypastas.show', [$variant, $variant->slug]))
        ->assertOk()
        ->assertSee(__('public.variants.variant_of'))
        ->assertSee('Título del padre visible')
        ->assertSee(route('copypastas.show', [$parent, $parent->slug]), false);
});

test('con el padre oculto o borrado el detalle de la variante muestra Contenido retirado sin enlace', function (string $state): void {
    $parent = Copypasta::factory()->create(['title' => 'Título del padre retirado']);
    $variant = Copypasta::factory()->variantOf($parent)->create();

    $state === 'oculto' ? $parent->forceFill(['hidden_at' => now(), 'hidden_reason' => 'x'])->save() : $parent->delete();

    $this->get(route('copypastas.show', [$variant, $variant->slug]))
        ->assertOk()
        ->assertSee(__('public.variants.removed'))
        ->assertDontSee('Título del padre retirado')
        ->assertDontSee(route('copypastas.show', [$parent, $parent->slug]), false);
})->with(['oculto', 'borrado']);

test('una variante cuyo padre desapareció físicamente sigue visible y sin enlace', function (): void {
    $parent = Copypasta::factory()->create();
    $variant = Copypasta::factory()->variantOf($parent)->create();

    $parent->forceDelete();

    expect($variant->refresh()->parent_id)->toBeNull();

    $this->get(route('copypastas.show', [$variant, $variant->slug]))->assertOk()->assertDontSee(__('public.variants.variant_of'));
});

test('el detalle lista las variantes directas por score con el @username de cada autor', function (): void {
    $parent = Copypasta::factory()->create();
    $low = Copypasta::factory()->variantOf($parent)->for(User::factory()->create(['username' => 'bajo_score']), 'user')->create(['score' => 2]);
    $high = Copypasta::factory()->variantOf($parent)->for(User::factory()->create(['username' => 'alto_score']), 'user')->create(['score' => 50]);
    Copypasta::factory()->variantOf($low)->create(['title' => 'Nieta que no sale en la lista del abuelo']);

    $this->get(route('copypastas.show', [$parent, $parent->slug]))
        ->assertOk()
        ->assertSeeInOrder([__('public.variants.original'), '@alto_score', '@bajo_score'])
        ->assertDontSee('Nieta que no sale en la lista del abuelo');
});

test('un autor anonimizado aparece como usuario eliminado en su pestaña', function (): void {
    $parent = Copypasta::factory()->create();
    Copypasta::factory()->variantOf($parent)->for(User::factory()->create(['anonymized_at' => now()]), 'user')->create();

    $this->get(route('copypastas.show', [$parent, $parent->slug]))->assertSee(__('public.variants.deleted_author'));
});

test('una variante oculta o borrada no aparece en la lista del padre para otros ni para su autor', function (): void {
    $parent = Copypasta::factory()->create();
    $author = User::factory()->create(['username' => 'autor_oculto']);
    $hidden = Copypasta::factory()->variantOf($parent)->for($author, 'user')->create();
    $deleted = Copypasta::factory()->variantOf($parent)->for(User::factory()->create(['username' => 'autor_borrado']), 'user')->create();
    Copypasta::factory()->variantOf($parent)->for(User::factory()->create(['username' => 'autor_visible']), 'user')->create();

    app(HideCopypasta::class)->handle(User::factory()->moderator()->create(), $hidden, 'Incumple las normas');
    $deleted->delete();

    foreach ([null, User::factory()->create(), $author] as $viewer) {
        $response = ($viewer === null ? $this : $this->actingAs($viewer))->get(route('copypastas.show', [$parent, $parent->slug]));

        $response->assertSee('@autor_visible')->assertDontSee('@autor_oculto')->assertDontSee('@autor_borrado');
    }
});

test('con más de 10 variantes salen 10 pestañas y el resto se resume sin enlace', function (): void {
    $parent = Copypasta::factory()->create();
    Copypasta::factory()->variantOf($parent)->count(13)->create();

    $response = $this->get(route('copypastas.show', [$parent, $parent->slug]))->assertOk();

    $response->assertSee('y 3 variantes más')->assertSee('id="variants-tab-v10"', false)->assertDontSee('id="variants-tab-v11"', false);
});

test('sin variantes visibles el detalle no muestra pestañas', function (): void {
    $parent = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$parent, $parent->slug]))->assertOk()->assertDontSee('variants-tab-original', false);
});

test('el botón Crear variante solo aparece a un miembro verificado que no es el autor', function (): void {
    $parent = Copypasta::factory()->create();
    $url = route('copypastas.show', [$parent, $parent->slug]);

    $this->get($url)->assertDontSee(__('public.variants.create'));
    $this->actingAs(User::factory()->unverified()->create())->get($url)->assertDontSee(__('public.variants.create'));
    $this->actingAs($parent->user)->get($url)->assertDontSee(__('public.variants.create'));
    $this->actingAs(User::factory()->create())->get($url)->assertSee(__('public.variants.create'))->assertSee(route('copypastas.variant', $parent), false);
});

test('la tarjeta de una plantilla lleva el distintivo y la de un copy-pasta normal no', function (): void {
    $template = Copypasta::factory()->template()->create();
    $plain = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$template, $template->slug]))->assertSee(__('ui.card.template'));
    $this->get(route('copypastas.show', [$plain, $plain->slug]))->assertDontSee(__('ui.card.template'));
});

test('la imagen para compartir lleva el cuerpo de la plantilla con las variables tal cual', function (): void {
    $template = Copypasta::factory()->template()->create(['body' => 'Hola {{nombre}}, soy {{ Ciudad }}.']);

    $this->get(route('copypastas.show', [$template, $template->slug]))->assertSee('Hola {{nombre}}, soy {{ Ciudad }}.', false);
});
