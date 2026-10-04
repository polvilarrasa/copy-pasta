<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class UpdateCopypasta
{
    /**
     * Edits keep moderation state. Tags that were deactivated after publishing stay attached.
     *
     * @param  array{title: string, body: string, is_nsfw: bool, tag_ids: array<int, int|string>}  $data
     */
    public function handle(User $editor, Copypasta $copypasta, array $data): Copypasta
    {
        Gate::forUser($editor)->authorize('update', $copypasta);

        $tagIds = app(ResolveCopypastaTags::class)->handle($data['tag_ids']);

        return DB::transaction(function () use ($copypasta, $data, $tagIds): Copypasta {
            $copypasta->forceFill([
                'title' => $data['title'],
                'slug' => Str::slug($data['title']),
                'body' => $data['body'],
                'is_nsfw' => $data['is_nsfw'],
                'edited_at' => now(),
            ])->save();

            $keptInactiveTagIds = $copypasta->tags()->where('is_active', false)->pluck('tags.id')->all();

            $copypasta->tags()->sync([...$tagIds, ...$keptInactiveTagIds]);

            return $copypasta;
        });
    }
}
