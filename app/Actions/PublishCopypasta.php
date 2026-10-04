<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class PublishCopypasta
{
    /**
     * @param  array{title: string, body: string, is_nsfw: bool, tag_ids: array<int, int|string>}  $data
     */
    public function handle(User $author, array $data): Copypasta
    {
        Gate::forUser($author)->authorize('create', Copypasta::class);

        $tagIds = app(ResolveCopypastaTags::class)->handle($data['tag_ids']);

        return DB::transaction(function () use ($author, $data, $tagIds): Copypasta {
            $copypasta = Copypasta::query()->create([
                'user_id' => $author->getKey(),
                'title' => $data['title'],
                'body' => $data['body'],
                'is_nsfw' => $data['is_nsfw'],
                'published_at' => now(),
            ]);

            $copypasta->tags()->attach($tagIds);

            return $copypasta;
        });
    }
}
