<?php

declare(strict_types=1);

use App\Actions\AnonymizeUser;
use App\Actions\ChangeUsername;
use App\Models\User;

test('el username actual responde 404 hasta que exista el perfil público', function (): void {
    User::factory()->create(['username' => 'ana']);

    $this->get('/u/ana')->assertNotFound();
});

test('un username antiguo redirige con 301 al actual', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $this->get('/u/ana')->assertRedirect('/u/ana_nueva')->assertStatus(301);
});

test('un username antiguo deja de redirigir a los 90 días', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    $this->travel(91)->days();

    $this->get('/u/ana')->assertNotFound();
});

test('un username de una cuenta anonimizada no redirige a nada', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');
    app(AnonymizeUser::class)->handle($member->refresh(), null);

    $this->get('/u/ana')->assertNotFound();
});

test('si otra cuenta ocupa el nombre antiguo, manda la cuenta actual y no hay redirección', function (): void {
    $member = User::factory()->create(['username' => 'ana']);
    app(ChangeUsername::class)->handle($member, 'ana_nueva');

    User::factory()->create(['username' => 'ana']);

    $this->get('/u/ana')->assertNotFound();
});
