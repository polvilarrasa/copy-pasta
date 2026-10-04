<?php

declare(strict_types=1);

use App\Enums\Role;
use App\Filament\Admin\Resources\Users\Pages\CreateUser;
use App\Filament\Admin\Resources\Users\Pages\ListUsers;
use App\Filament\Admin\Resources\Users\UserResource;
use App\Mail\UserInvitationMail;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

beforeEach(fn () => Filament::setCurrentPanel(Filament::getPanel('admin')));

test('un admin crea un usuario desde el panel con su rol y le envía una invitación', function (): void {
    Mail::fake();
    $admin = User::factory()->admin()->withTwoFactor()->create();

    Livewire::actingAs($admin)
        ->test(CreateUser::class)
        ->fillForm([
            'username' => 'nuevo',
            'email' => 'nuevo@example.com',
            'role' => Role::Moderator->value,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = User::query()->where('email', 'nuevo@example.com')->sole();

    expect($created->role)->toBe(Role::Moderator)
        ->and($created->email_verified_at)->toBeNull();

    Mail::assertQueued(UserInvitationMail::class, fn (UserInvitationMail $mail): bool => $mail->hasTo('nuevo@example.com'));
});

test('el email de los usuarios solo lo ve un admin, y un moderador no lo puede buscar', function (): void {
    $member = User::factory()->create(['email' => 'secreto@example.com']);

    Livewire::actingAs(User::factory()->admin()->withTwoFactor()->create())
        ->test(ListUsers::class)
        ->assertTableColumnVisible('email');

    Livewire::actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->test(ListUsers::class)
        ->assertTableColumnHidden('email')
        ->searchTable('secreto')
        ->assertCanNotSeeTableRecords([$member]);
});

test('un moderador no abre el formulario de creación de usuarios', function (): void {
    $this->actingAs(User::factory()->moderator()->withTwoFactor()->create())
        ->get(UserResource::getUrl('create'))
        ->assertForbidden();
});

test('un admin borra a un miembro desde la tabla y puede restaurarlo', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('delete')->table($member))
        ->assertHasNoActionErrors();

    expect($member->refresh()->trashed())->toBeTrue();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('restore')->table($member))
        ->assertHasNoActionErrors();

    expect($member->refresh()->trashed())->toBeFalse();
});

test('un admin marca como verificado a un miembro desde la tabla', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->unverified()->create();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->callAction(TestAction::make('verifyEmail')->table($member))
        ->assertHasNoActionErrors();

    expect($member->refresh()->hasVerifiedEmail())->toBeTrue();
});

test('un usuario borrado solo ofrece restaurar en la tabla', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $member->delete();

    Livewire::actingAs($admin)
        ->test(ListUsers::class)
        ->assertTableActionHidden('ban', $member)
        ->assertTableActionHidden('delete', $member)
        ->assertTableActionVisible('restore', $member);
});

test('un admin no puede editar un usuario borrado, ni siquiera por URL', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $member->delete();

    $this->actingAs($admin)
        ->get(UserResource::getUrl('edit', ['record' => $member]))
        ->assertForbidden();
});

test('un admin abre la ficha de un usuario borrado para poder restaurarlo', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $member->delete();

    $this->actingAs($admin)
        ->get(UserResource::getUrl('view', ['record' => $member]))
        ->assertOk();
});
