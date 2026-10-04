<?php

declare(strict_types=1);

use App\Models\Copypasta;
use App\Models\Report;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Database\QueryException;

test('impide borrar físicamente a un usuario que tiene datos ajenos a su cuenta', function (Closure $createOwnedRecord): void {
    $user = User::factory()->create();
    $createOwnedRecord($user);

    expect(fn () => $user->forceDelete())->toThrow(QueryException::class);
})->with([
    'copy-pasta' => [fn (User $user): Copypasta => Copypasta::factory()->create(['user_id' => $user->getKey()])],
    'voto' => [fn (User $user): Vote => Vote::factory()->create(['user_id' => $user->getKey()])],
    'reporte' => [fn (User $user): Report => Report::factory()->create(['reporter_id' => $user->getKey()])],
]);
