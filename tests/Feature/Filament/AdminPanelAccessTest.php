<?php

declare(strict_types=1);

use App\Models\User;
use Filament\Panel;

test('un usuario normal recibe 403 en el panel de administración', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin')->assertForbidden();
});

test('un invitado es redirigido al login de la aplicación', function (): void {
    $this->get('/admin')->assertRedirect('/login');
});

test('un moderador accede al panel de administración', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();

    $this->actingAs($moderator)->get('/admin')->assertOk();
});

test('un moderador baneado no accede al panel aunque tenga sesión', function (): void {
    $moderator = User::factory()->moderator()->banned()->create();

    expect($moderator->canAccessPanel(Panel::make()->id('admin')))->toBeFalse();
});

test('el panel de usuario se abre a miembros verificados, también a staff, pero no sin verificar', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $unverified = User::factory()->unverified()->create();

    expect($admin->canAccessPanel(Panel::make()->id('app')))->toBeTrue()
        ->and($unverified->canAccessPanel(Panel::make()->id('app')))->toBeFalse();
});
