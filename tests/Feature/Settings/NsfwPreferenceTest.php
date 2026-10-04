<?php

declare(strict_types=1);

use App\Livewire\Feed;
use App\Models\Copypasta;
use App\Models\User;
use Livewire\Livewire;

test('activar la preferencia +18 exige confirmar la mayoría de edad y guarda la fecha', function (): void {
    $user = User::factory()->create(['show_nsfw' => false]);
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('showNsfw', true)
        ->set('ageConfirmed', true)
        ->call('updateNsfwPreference')
        ->assertHasNoErrors();

    expect($user->refresh())
        ->show_nsfw->toBeTrue()
        ->nsfw_confirmed_at->not->toBeNull();
});

test('sin confirmar la mayoría de edad la preferencia +18 no se activa', function (): void {
    $user = User::factory()->create(['show_nsfw' => false]);
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('showNsfw', true)
        ->call('updateNsfwPreference')
        ->assertHasErrors(['ageConfirmed']);

    expect($user->refresh())
        ->show_nsfw->toBeFalse()
        ->nsfw_confirmed_at->toBeNull();
});

test('desactivar la preferencia +18 no pide confirmación', function (): void {
    $user = User::factory()->create(['show_nsfw' => true, 'nsfw_confirmed_at' => now()]);
    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('showNsfw', false)
        ->call('updateNsfwPreference')
        ->assertHasNoErrors();

    expect($user->refresh()->show_nsfw)->toBeFalse();
});

test('un miembro con la preferencia +18 sin confirmar no ve contenido +18 en el feed', function (): void {
    Copypasta::factory()->nsfw()->create(['title' => 'Titulo adulto']);

    Livewire::actingAs(User::factory()->create(['show_nsfw' => true, 'nsfw_confirmed_at' => null]))
        ->test(Feed::class)
        ->assertDontSee('Titulo adulto');
});
