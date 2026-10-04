<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\ModerationActionType;
use App\Models\ModerationAction;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class SaveTag
{
    /**
     * Create or update a tag and record the moderation action.
     *
     * @param  array{name: string, slug?: string|null, color: string, is_active: bool}  $attributes
     */
    public function handle(User $actor, array $attributes, ?Tag $tag = null): Tag
    {
        $attributes['slug'] = blank($attributes['slug'] ?? null)
            ? Str::slug($attributes['name'])
            : $attributes['slug'];

        if ($tag === null) {
            Gate::forUser($actor)->authorize('create', Tag::class);
        } else {
            Gate::forUser($actor)->authorize('update', $tag);
        }

        return DB::transaction(function () use ($actor, $attributes, $tag): Tag {
            $isNew = $tag === null;
            $tag ??= new Tag;
            $tag->fill($attributes)->save();

            ModerationAction::query()->create([
                'actor_id' => $actor->getKey(),
                'action' => $isNew ? ModerationActionType::TagCreated : ModerationActionType::TagUpdated,
                'subject_type' => Tag::class,
                'subject_id' => (string) $tag->getKey(),
                'meta' => ['attributes' => $attributes],
            ]);

            return $tag;
        });
    }
}
