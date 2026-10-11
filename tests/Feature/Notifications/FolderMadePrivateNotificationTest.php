<?php

declare(strict_types=1);

use App\Actions\MakeFolderPrivate;
use App\Enums\NotificationType;
use App\Models\Folder;
use App\Models\User;
use App\Support\NotificationPresenter;

test('hacer privada una carpeta notifica al dueño con el motivo, aunque tenga lo opcional desactivado', function (): void {
    $owner = User::factory()->create(['notification_prefs' => ['milestone' => false, 'report_accepted' => false]]);
    $folder = Folder::factory()->public()->for($owner)->create(['name' => 'Carpeta moderada']);

    app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Contenido que incumple las normas');

    $notification = $owner->notifications()->sole();

    expect($notification->type)->toBe(NotificationType::FolderMadePrivate->value)
        ->and($notification->data)->toEqual([
            'type' => 'folder_made_private',
            'folder_id' => $folder->id,
            'name' => 'Carpeta moderada',
            'reason' => 'Contenido que incumple las normas',
        ])
        ->and(NotificationType::FolderMadePrivate->isMandatory())->toBeTrue();
});

test('la notificación dice el motivo y enlaza a la carpeta mientras exista', function (): void {
    $owner = User::factory()->create();
    $folder = Folder::factory()->public()->for($owner)->create(['name' => 'Carpeta moderada']);
    app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Spam');

    $item = app(NotificationPresenter::class)->present($owner->notifications()->get())->sole();

    expect($item->text)->toBe('El equipo de moderación ha hecho privada tu carpeta «Carpeta moderada». Motivo: Spam')
        ->and($item->url)->toBe(route('folders.show', $folder));

    $folder->delete();

    $item = app(NotificationPresenter::class)->present($owner->notifications()->get())->sole();

    expect($item->url)->toBeNull();
});

test('un dueño baneado no recibe la notificación', function (): void {
    $owner = User::factory()->banned()->create();
    $folder = Folder::factory()->public()->for($owner)->create();

    app(MakeFolderPrivate::class)->handle(User::factory()->moderator()->create(), $folder, 'Spam');

    expect($owner->notifications()->count())->toBe(0);
});
