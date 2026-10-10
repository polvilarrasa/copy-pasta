<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Jobs\SyncCopypastaOgImageJob;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\UnicodeText;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class UpdateCopypasta
{
    /**
     * Edits keep moderation state. Tags that were deactivated after publishing stay attached. A change of title or
     * body is kept as a new version, so reports and the public history still show the text that was there.
     *
     * @param  array{title: string, body: string, is_nsfw: bool, tag_ids: array<int, int|string>}  $data
     */
    public function handle(User $editor, Copypasta $copypasta, array $data): Copypasta
    {
        Gate::forUser($editor)->authorize('update', $copypasta);

        $title = UnicodeText::cleanTitle($data['title']);

        throw_if($title === '', ValidationException::withMessages(['title' => __('app.errors.title_empty')]));

        $tagIds = app(ResolveCopypastaTags::class)->handle($data['tag_ids']);

        $updated = DB::transaction(function () use ($copypasta, $data, $title, $tagIds): Copypasta {
            $textChanged = $copypasta->title !== $title || $copypasta->body !== $data['body'];

            $copypasta->forceFill([
                'title' => $title,
                'slug' => Str::slug($title) ?: $copypasta->getKey(),
                'body' => $data['body'],
                'is_nsfw' => $data['is_nsfw'],
                'edited_at' => now(),
            ])->save();

            $keptInactiveTagIds = $copypasta->tags()->where('is_active', false)->pluck('tags.id')->all();

            $copypasta->tags()->sync([...$tagIds, ...$keptInactiveTagIds]);

            if ($textChanged) {
                app(SaveCopypastaRevision::class)->handle($copypasta);
            }

            return $copypasta;
        });

        SyncCopypastaOgImageJob::dispatch($updated->getKey());

        app(RecordEvent::class)->handle(EventType::Update, $editor, $updated);

        return $updated;
    }
}
