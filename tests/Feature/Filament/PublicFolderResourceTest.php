<?php

declare(strict_types=1);

use App\Actions\MakeFolderPrivate;
use App\Actions\SetFolderVisibility;
use App\Enums\ModerationActionType;
use App\Filament\Admin\Resources\PublicFolders\Pages\ListPublicFolders;
use App\Models\Folder;
use App\Models\ModerationAction;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;

test('el staff ve solo las carpetas públicas', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $public = Folder::factory()->public()->create();
    $private = Folder::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->assertCanSeeTableRecords([$public])
        ->assertCanNotSeeTableRecords([$private]);
});

test('el listado incluye las carpetas bloqueadas como privadas y no las privadas sin bloqueo', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $locked = Folder::factory()->create();
    $locked->forceFill(['public_locked_at' => now(), 'public_lock_reason' => 'Motivo'])->save();
    $plain = Folder::factory()->create();

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->assertCanSeeTableRecords([$locked])
        ->assertCanNotSeeTableRecords([$plain]);
});

test('un usuario normal no accede al listado de carpetas públicas', function (): void {
    $this->actingAs(User::factory()->create())->get('/admin/carpetas-publicas')->assertForbidden();
});

test('el staff vuelve privada una carpeta con motivo y queda en el log, y su URL pública responde 404', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $folder = Folder::factory()->public()->create(['name' => 'Carpeta problemática']);
    $publicId = $folder->public_id;

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->callAction(TestAction::make('makePrivate')->table($folder), data: ['reason' => 'Contenido que incumple las normas'])
        ->assertHasNoActionErrors();

    expect($folder->refresh()->is_public)->toBeFalse();

    $log = ModerationAction::query()->where('action', ModerationActionType::MakeFolderPrivate)->sole();

    expect($log)
        ->actor_id->toBe($moderator->id)
        ->subject_id->toBe((string) $folder->id)
        ->reason->toBe('Contenido que incumple las normas')
        ->meta->toMatchArray(['owner_id' => $folder->user_id, 'name' => 'Carpeta problemática', 'public_id' => $publicId]);

    $this->get(route('folders.public', $publicId))->assertNotFound();
});

test('volver privada sin motivo muestra error de validación y no cambia nada', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $folder = Folder::factory()->public()->create();

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->callAction(TestAction::make('makePrivate')->table($folder), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($folder->refresh()->is_public)->toBeTrue()->and(ModerationAction::query()->count())->toBe(0);
});

test('volver privada una carpeta la bloquea con el motivo', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $folder = Folder::factory()->public()->create();

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->callAction(TestAction::make('makePrivate')->table($folder), data: ['reason' => 'Contenido que incumple las normas']);

    expect($folder->refresh())
        ->is_public->toBeFalse()
        ->public_locked_at->not->toBeNull()
        ->public_lock_reason->toBe('Contenido que incumple las normas');
});

test('el staff desbloquea una carpeta con motivo: queda privada, el dueño puede decidir y queda en el log', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $folder = Folder::factory()->public()->create();
    app(MakeFolderPrivate::class)->handle($moderator, $folder, 'Contenido que incumple las normas');

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->callAction(TestAction::make('unlock')->table($folder), data: ['reason' => 'El dueño ha limpiado la carpeta'])
        ->assertHasNoActionErrors();

    $folder->refresh();

    expect($folder->is_public)->toBeFalse()
        ->and($folder->public_locked_at)->toBeNull()
        ->and($folder->public_lock_reason)->toBeNull();

    $log = ModerationAction::query()->where('action', ModerationActionType::UnlockFolder)->sole();

    expect($log)
        ->actor_id->toBe($moderator->id)
        ->subject_id->toBe((string) $folder->id)
        ->reason->toBe('El dueño ha limpiado la carpeta')
        ->meta->toMatchArray(['owner_id' => $folder->user_id, 'lock_reason' => 'Contenido que incumple las normas']);

    app(SetFolderVisibility::class)->handle($folder->user, $folder, true);

    expect($folder->refresh()->is_public)->toBeTrue();
});

test('desbloquear sin motivo muestra error de validación y no cambia nada', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $folder = Folder::factory()->public()->create();
    app(MakeFolderPrivate::class)->handle($moderator, $folder, 'Motivo');

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->callAction(TestAction::make('unlock')->table($folder), data: ['reason' => ''])
        ->assertHasActionErrors(['reason' => 'required']);

    expect($folder->refresh()->isPublicLocked())->toBeTrue()
        ->and(ModerationAction::query()->where('action', ModerationActionType::UnlockFolder)->count())->toBe(0);
});

test('la acción de desbloquear solo aparece en las bloqueadas y la de hacer privada solo en las públicas', function (): void {
    $moderator = User::factory()->moderator()->withTwoFactor()->create();
    $public = Folder::factory()->public()->create();
    $locked = Folder::factory()->create();
    $locked->forceFill(['public_locked_at' => now(), 'public_lock_reason' => 'Motivo'])->save();

    Livewire::actingAs($moderator)
        ->test(ListPublicFolders::class)
        ->assertTableActionVisible('makePrivate', $public)
        ->assertTableActionHidden('unlock', $public)
        ->assertTableActionVisible('unlock', $locked)
        ->assertTableActionHidden('makePrivate', $locked);
});
