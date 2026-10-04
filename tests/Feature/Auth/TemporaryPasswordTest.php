<?php

declare(strict_types=1);

use App\Actions\CreateUserByAdmin;
use App\Actions\ImpersonateUser;
use App\Enums\Role;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

test('un miembro con contraseña temporal es enviado al cambio antes de llegar al panel', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get('/app')
        ->assertRedirect(route('password.temporary.edit'));
});

test('el formulario de cambio se muestra a quien tiene contraseña temporal', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->get(route('password.temporary.edit'))
        ->assertOk();
});

test('un admin que actúa como un miembro con contraseña temporal no es redirigido', function (): void {
    $admin = User::factory()->admin()->create();
    $member = User::factory()->create(['must_change_password' => true]);
    $this->actingAs($admin);

    app(ImpersonateUser::class)->handle($admin, $member);

    $this->get('/app')->assertOk();
});

test('cambiar la contraseña temporal levanta la obligación y abre el panel', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->put(route('password.temporary.update'), [
            'current_password' => 'password',
            'password' => 'Nueva-clave-segura-1',
            'password_confirmation' => 'Nueva-clave-segura-1',
        ])
        ->assertRedirect();

    expect($user->refresh()->must_change_password)->toBeFalse()
        ->and(Hash::check('Nueva-clave-segura-1', $user->password))->toBeTrue();

    $this->get('/app')->assertOk();
});

test('no se acepta la contraseña temporal como nueva contraseña', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);

    $this->actingAs($user)
        ->put(route('password.temporary.update'), [
            'current_password' => 'password',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertSessionHasErrors('password');

    expect($user->refresh()->must_change_password)->toBeTrue();
});

test('un admin que crea un usuario recibe una contraseña que el nuevo usuario puede usar para entrar y cambiar', function (): void {
    $admin = User::factory()->admin()->create();
    $created = app(CreateUserByAdmin::class)->handle($admin, 'nuevo', 'nuevo@example.com', Role::User);

    expect(Auth::attempt(['email' => 'nuevo@example.com', 'password' => $created['temporary_password']]))->toBeTrue();

    $this->get('/app')->assertRedirect(route('password.temporary.edit'));
});

test('cambiar la contraseña desde cualquier otro sitio también levanta la obligación', function (): void {
    $user = User::factory()->create(['must_change_password' => true]);

    $user->forceFill(['password' => 'otra-clave-distinta'])->save();

    expect($user->refresh()->must_change_password)->toBeFalse();
});

test('un usuario borrado lógicamente no puede entrar con sus credenciales', function (): void {
    $user = User::factory()->create(['email' => 'borrado@example.com']);
    $user->delete();

    expect(Auth::attempt(['email' => 'borrado@example.com', 'password' => 'password']))->toBeFalse();
});
