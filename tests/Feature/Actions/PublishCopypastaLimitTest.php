<?php

declare(strict_types=1);

use App\Actions\PublishCopypasta;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Http\Exceptions\ThrottleRequestsException;

function publishLimitData(Tag $tag): array
{
    return [
        'title' => fake()->unique()->sentence(4),
        'body' => fake()->unique()->paragraph(),
        'is_nsfw' => false,
        'tag_ids' => [$tag->getKey()],
    ];
}

test('un autor publica como máximo diez copy-pastas por hora', function (): void {
    $author = User::factory()->create();
    $tag = Tag::factory()->create();

    foreach (range(1, PublishCopypasta::MAX_PUBLICATIONS_PER_HOUR) as $_) {
        app(PublishCopypasta::class)->handle($author, publishLimitData($tag));
    }

    app(PublishCopypasta::class)->handle($author, publishLimitData($tag));
})->throws(ThrottleRequestsException::class);

test('el límite de publicación es por autor', function (): void {
    $tag = Tag::factory()->create();
    $first = User::factory()->create();
    $second = User::factory()->create();

    foreach (range(1, PublishCopypasta::MAX_PUBLICATIONS_PER_HOUR) as $_) {
        app(PublishCopypasta::class)->handle($first, publishLimitData($tag));
    }

    expect(app(PublishCopypasta::class)->handle($second, publishLimitData($tag))->user_id)->toBe($second->id);
});
