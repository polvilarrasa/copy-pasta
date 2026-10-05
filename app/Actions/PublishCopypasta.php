<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\User;
use App\Support\UnicodeText;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class PublishCopypasta
{
    public const MAX_PUBLICATIONS_PER_HOUR = 10;

    /**
     * @param  array{title: string, body: string, is_nsfw: bool, tag_ids: array<int, int|string>}  $data
     */
    public function handle(User $author, array $data): Copypasta
    {
        Gate::forUser($author)->authorize('create', Copypasta::class);

        throw_unless(
            RateLimiter::attempt('publish:'.$author->getKey(), self::MAX_PUBLICATIONS_PER_HOUR, fn (): bool => true, 3600),
            new ThrottleRequestsException(__('app.errors.publish_rate_limited')),
        );

        $title = UnicodeText::cleanTitle($data['title']);

        throw_if($title === '', ValidationException::withMessages(['title' => __('app.errors.title_empty')]));

        $tagIds = app(ResolveCopypastaTags::class)->handle($data['tag_ids']);

        $copypasta = DB::transaction(function () use ($author, $data, $title, $tagIds): Copypasta {
            $copypasta = Copypasta::query()->create([
                'user_id' => $author->getKey(),
                'title' => $title,
                'body' => $data['body'],
                'is_nsfw' => $data['is_nsfw'],
                'published_at' => now(),
            ]);

            $copypasta->tags()->attach($tagIds);

            app(SaveCopypastaRevision::class)->handle($copypasta);

            return $copypasta;
        });

        app(RecordEvent::class)->handle(EventType::Publish, $author, $copypasta);

        return $copypasta;
    }
}
