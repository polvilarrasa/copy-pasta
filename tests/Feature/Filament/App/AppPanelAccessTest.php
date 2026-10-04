<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\User;

test('un invitado es redirigido al login desde el panel de usuario', function (): void {
    $this->get('/app/copypastas')->assertRedirect(route('login'));
});

test('un usuario sin email verificado entra a su lista, pero no puede publicar', function (): void {
    $this->actingAs(User::factory()->unverified()->create())
        ->get('/app/copypastas')
        ->assertOk();

    $this->actingAs(User::factory()->unverified()->create())
        ->get('/app/copypastas/create')
        ->assertForbidden();
});

test('un miembro verificado accede a su lista de copy-pastas', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/app/copypastas')
        ->assertOk();
});

test('un miembro verificado ve el formulario de creación y de edición de los suyos', function (): void {
    $user = User::factory()->create();
    $own = Copypasta::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)->get('/app/copypastas/create')->assertOk();
    $this->actingAs($user)->get('/app/copypastas/'.$own->getKey().'/edit')->assertOk();
});

test('un miembro baneado no accede al panel aunque tenga sesión', function (): void {
    $this->actingAs(User::factory()->banned()->create())
        ->get('/app/copypastas')
        ->assertRedirect(route('login'));
});

test('el botón publicar de la web pública lleva al formulario para verificados y a verificar para el resto', function (): void {
    $this->actingAs(User::factory()->create())
        ->get(route('home'))
        ->assertSee('href="http://localhost/app/copypastas/create"', false);

    $this->actingAs(User::factory()->unverified()->create())
        ->get(route('home'))
        ->assertSee(route('verification.notice'), false);
});

test('los ajustes de cuenta viven solo en /settings: el panel de usuario ya no tiene perfil propio', function (): void {
    $this->actingAs(User::factory()->create())
        ->get('/app/profile')
        ->assertNotFound();

    $this->actingAs(User::factory()->create())
        ->get('/settings/profile')
        ->assertOk();
});
