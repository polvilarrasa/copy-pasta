<?php

declare(strict_types=1);

use App\Filament\App\Pages\Auth\EditProfile;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('app')));

test('el usuario cambia su username y activa la preferencia NSFW', function (): void {
    $user = User::factory()->create(['username' => 'viejo_nombre', 'show_nsfw' => false]);

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->fillForm([
            'username' => 'nuevo_nombre',
            'email' => $user->email,
            'show_nsfw' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh())
        ->username->toBe('nuevo_nombre')
        ->show_nsfw->toBeTrue();
});

test('no permite un username ya usado por otro miembro', function (): void {
    User::factory()->create(['username' => 'ocupado']);
    $user = User::factory()->create(['username' => 'libre']);

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->fillForm(['username' => 'ocupado', 'email' => $user->email])
        ->call('save')
        ->assertHasFormErrors(['username' => 'unique']);
});

test('cambiar el email exige verificarlo de nuevo', function (): void {
    Notification::fake();
    $user = User::factory()->create(['email_verified_at' => now()]);

    Livewire::actingAs($user)
        ->test(EditProfile::class)
        ->fillForm([
            'username' => $user->username,
            'email' => 'nuevo@ejemplo.test',
            'currentPassword' => 'password',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($user->refresh())
        ->email->toBe('nuevo@ejemplo.test')
        ->email_verified_at->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);
});
