<?php

declare(strict_types=1);

use App\Enums\Achievement;
use App\Models\Tag;
use App\Models\User;
use App\Models\UserAchievement;

test('publicar desbloquea un logro, avisa en la campana, se elige su título en ajustes y sale en la tarjeta', function (): void {
    $user = User::factory()->create(['username' => 'paco_nocturno']);
    Tag::factory()->create(['name' => 'humor', 'slug' => 'humor']);

    signInInBrowser($user);

    visitInteractive('/publicar')
        ->fill('title', 'Carta de amor a mi router')
        ->fill('body', 'Querido router, sé que no hablamos mucho pero siempre estás ahí.')
        ->click('#tag-humor')
        ->assertAriaAttribute('#tag-humor', 'pressed', 'true')
        ->press('@publish-submit-button')
        ->assertPathBeginsWith('/c/');

    expect(holdsAchievement($user, Achievement::FirstPaste))->toBeTrue();

    visitInteractive('/')
        ->assertSeeIn('@notification-count', '1')
        ->click('@notification-bell')
        ->assertSee('Has conseguido el logro «Primera pegada».');

    visitInteractive(route('title.edit'))
        ->click('Recién pegado')
        ->assertSee(__('achievements.settings.saved'));

    expect($user->refresh()->title_key)->toBe('first_paste');

    visit('/')->assertSee('Recién pegado');
});

test('en el perfil propio se ven los pendientes con su progreso y los secretos como ???', function (): void {
    $user = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($user)->ofAchievement(Achievement::FirstPaste)->create();

    signInInBrowser($user);

    visit('/u/paco_nocturno')
        ->assertSee('Primera pegada')
        ->assertSee(__('achievements.profile.pending'))
        ->assertSee(__('achievements.profile.secret_name'));
});

test('un visitante en el perfil de otro no ve pendientes', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::FirstPaste)->create();

    visit('/u/paco_nocturno')
        ->assertSee('Primera pegada')
        ->assertDontSee(__('achievements.profile.pending'));
});
