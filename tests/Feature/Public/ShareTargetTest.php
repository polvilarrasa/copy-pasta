<?php

declare(strict_types=1);

use App\Livewire\CopypastaForm;
use App\Models\Copypasta;
use App\Models\User;
use Livewire\Livewire;

test('un visitante sin sesión que comparte a /publicar va al login y después al formulario con los datos', function (): void {
    $shared = ['title' => 'Un título compartido', 'text' => 'El texto que viene de otra app'];
    $address = '/publicar?'.http_build_query($shared);
    $member = User::factory()->create();

    $this->get($address)->assertRedirect(route('login'));

    $location = (string) $this->post(route('login.store'), ['email' => $member->email, 'password' => 'password'])
        ->assertRedirect()
        ->headers->get('Location');

    parse_str((string) parse_url($location, PHP_URL_QUERY), $query);

    expect(parse_url($location, PHP_URL_PATH))->toBe('/publicar')
        ->and($query)->toEqual($shared);
});

test('el formulario arranca con el título y el texto compartidos', function (): void {
    $member = User::factory()->create();

    $this->actingAs($member)
        ->get('/publicar?'.http_build_query(['title' => 'Título compartido', 'text' => 'Cuerpo compartido desde Android']))
        ->assertOk()
        ->assertSee('Título compartido')
        ->assertSee('Cuerpo compartido desde Android');

    Livewire::actingAs($member)
        ->test(CopypastaForm::class, ['prefillTitle' => 'Título compartido', 'prefillBody' => 'Cuerpo compartido desde Android'])
        ->assertSet('title', 'Título compartido')
        ->assertSet('body', 'Cuerpo compartido desde Android');
});

test('el formulario recorta lo compartido a los límites de sus campos, contando caracteres y no bytes', function (): void {
    $member = User::factory()->create();

    Livewire::actingAs($member)
        ->test(CopypastaForm::class, [
            'prefillTitle' => str_repeat('ñ', CopypastaForm::MAX_TITLE_LENGTH + 30),
            'prefillBody' => str_repeat('é', CopypastaForm::MAX_BODY_LENGTH + 500),
        ])
        ->assertSet('title', str_repeat('ñ', CopypastaForm::MAX_TITLE_LENGTH))
        ->assertSet('body', str_repeat('é', CopypastaForm::MAX_BODY_LENGTH));
});

test('sin datos compartidos el formulario empieza vacío y los parámetros que no son texto se ignoran', function (): void {
    $member = User::factory()->create();

    $this->actingAs($member)->get('/publicar')->assertOk();
    $this->get('/publicar?title[]=uno&text[a]=dos')->assertOk();

    Livewire::actingAs($member)->test(CopypastaForm::class)->assertSet('title', '')->assertSet('body', '');
});

test('lo compartido solo rellena al publicar de cero, no al editar ni al crear una variante', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create(['title' => 'Título original']);

    Livewire::actingAs($member)
        ->test(CopypastaForm::class, ['copypasta' => $copypasta, 'prefillTitle' => 'Otro título', 'prefillBody' => 'Otro cuerpo'])
        ->assertSet('title', 'Título original');
});

test('un miembro sin verificar ve el aviso de verificación aunque comparta texto', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/publicar?title=Hola&text=Mundo')
        ->assertOk()
        ->assertSee(__('public.publish.unverified_title'));
});
