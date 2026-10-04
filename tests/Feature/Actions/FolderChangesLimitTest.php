<?php

declare(strict_types=1);

use App\Actions\AddToFolder;
use App\Actions\CreateFolder;
use App\Actions\DeleteFolder;
use App\Actions\RemoveFromFolder;
use App\Actions\RenameFolder;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\RateLimiter;

function exhaustFolderChanges(User $user): void
{
    foreach (range(1, CreateFolder::MAX_FOLDER_CHANGES_PER_MINUTE) as $_) {
        RateLimiter::attempt('folder-changes:'.$user->getKey(), CreateFolder::MAX_FOLDER_CHANGES_PER_MINUTE, fn (): bool => true, 60);
    }
}

dataset('cambios de carpeta', [
    'crear' => [fn (User $user): mixed => app(CreateFolder::class)->handle($user, 'Nueva')],
    'renombrar' => [fn (User $user): mixed => app(RenameFolder::class)->handle($user, Folder::factory()->for($user)->create(), 'Otra')],
    'borrar' => [fn (User $user): mixed => app(DeleteFolder::class)->handle($user, Folder::factory()->for($user)->create())],
    'añadir' => [fn (User $user): mixed => app(AddToFolder::class)->handle($user, Folder::factory()->for($user)->create(), Copypasta::factory()->create())],
    'quitar' => [fn (User $user): mixed => app(RemoveFromFolder::class)->handle($user, Folder::factory()->for($user)->create(), Copypasta::factory()->create())],
]);

test('superado el límite, cualquier cambio de carpeta responde con 429', function (Closure $change): void {
    $user = User::factory()->create();
    exhaustFolderChanges($user);

    $change($user);
})->with('cambios de carpeta')->throws(ThrottleRequestsException::class);

test('el límite de cambios en carpetas es por usuario', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();
    exhaustFolderChanges($first);

    expect(app(CreateFolder::class)->handle($second, 'Nueva')->user_id)->toBe($second->getKey());
});
