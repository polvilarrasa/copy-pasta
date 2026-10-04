<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Folder;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class CreateFolder
{
    /**
     * Creates a named folder at the end of the member's list. The count check runs under a row lock so two
     * simultaneous requests cannot both slip past the limit.
     */
    public function handle(User $user, string $name): Folder
    {
        Gate::forUser($user)->authorize('create', Folder::class);

        $name = trim($name);

        return DB::transaction(function () use ($user, $name): Folder {
            User::query()->whereKey($user->getKey())->lockForUpdate()->firstOrFail();

            $folders = Folder::query()->where('user_id', $user->getKey());

            if ((clone $folders)->count() >= Folder::MAX_PER_USER) {
                throw ValidationException::withMessages([
                    'name' => __('app.folders.errors.limit', ['max' => Folder::MAX_PER_USER]),
                ]);
            }

            if ((clone $folders)->where('name', $name)->exists()) {
                throw ValidationException::withMessages(['name' => __('app.folders.errors.name_taken')]);
            }

            return Folder::query()->create([
                'user_id' => $user->getKey(),
                'name' => $name,
                'is_default' => false,
                'position' => (int) (clone $folders)->max('position') + 1,
            ]);
        });
    }
}
