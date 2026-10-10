<?php

declare(strict_types=1);

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
