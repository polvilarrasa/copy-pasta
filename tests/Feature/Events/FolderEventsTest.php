<?php

declare(strict_types=1);

use App\Actions\CreateFolder;
use App\Actions\DeleteFolder;
use App\Actions\RenameFolder;
use App\Enums\EventType;
use App\Models\Folder;
use App\Models\TrackedEvent;
use App\Models\User;

beforeEach(fn () => prepareEventPartitions());

test('crear una carpeta registra un evento folder_create', function (): void {
    $member = User::factory()->create();

    app(CreateFolder::class)->handle($member, 'Mis favoritos de verano');

    expect(TrackedEvent::query()->sole())
        ->type->toBe(EventType::FolderCreate)
        ->user_id->toBe($member->getKey());
});

test('renombrar una carpeta registra un evento folder_rename', function (): void {
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create();

    app(RenameFolder::class)->handle($member, $folder, 'Otro nombre');

    expect(TrackedEvent::query()->sole()->type)->toBe(EventType::FolderRename);
});

test('borrar una carpeta registra un evento folder_delete', function (): void {
    $member = User::factory()->create();
    $folder = Folder::factory()->for($member)->create();

    app(DeleteFolder::class)->handle($member, $folder);

    expect(TrackedEvent::query()->sole()->type)->toBe(EventType::FolderDelete);
});
