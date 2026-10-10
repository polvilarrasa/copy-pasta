<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DismissCopypasta;
use App\Actions\RecordCopypastaCopy;
use App\Actions\ToggleFavorite;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\Tag;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Every stored score as "user:tag => [score, updated_at]", the shape both the live calculation and the rebuild fill.
 *
 * @return array<string, array{float, string}>
 */
function affinitySnapshot(): array
{
    return DB::table('user_tag_affinities')->orderBy('user_id')->orderBy('tag_id')->get()
        ->mapWithKeys(fn (object $row): array => [$row->user_id.':'.$row->tag_id => [round((float) $row->score, 6), (string) $row->updated_at]])
        ->all();
}

function liveActivity(): void
{
    [$humor, $chat, $gaming] = Tag::factory()->count(3)->create();
    $members = User::factory()->count(2)->create();

    $a = Copypasta::factory()->create();
    $a->tags()->attach([$humor->id, $chat->id]);
    $b = Copypasta::factory()->create();
    $b->tags()->attach([$chat->id, $gaming->id]);
    $c = Copypasta::factory()->create();
    $c->tags()->attach([$gaming->id]);

    Carbon::setTestNow(Carbon::parse('2026-09-01 10:00:00'));
    app(CastVote::class)->handle($members[0], $a, 1);
    app(CastVote::class)->handle($members[1], $b, -1);

    Carbon::setTestNow(Carbon::parse('2026-09-12 18:30:00'));
    app(RecordCopypastaCopy::class)->handle($b, 'k1', $members[0]);
    app(ToggleFavorite::class)->handle($members[1], $a);

    Carbon::setTestNow(Carbon::parse('2026-10-02 08:15:00'));
    app(DismissCopypasta::class)->handle($members[0], $c);
    app(CastVote::class)->handle($members[1], $c, 1);

    Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00'));
}

test('app:rebuild-affinities da el mismo resultado que el cálculo en vivo, con el decaimiento de la fecha real de cada señal', function (): void {
    liveActivity();
    $live = affinitySnapshot();
    expect($live)->not->toBeEmpty();

    Artisan::call('app:rebuild-affinities');

    expect(affinitySnapshot())->toEqual($live);
});

test('ejecutarlo dos veces da lo mismo', function (): void {
    liveActivity();

    Artisan::call('app:rebuild-affinities');
    $first = affinitySnapshot();
    Artisan::call('app:rebuild-affinities');

    expect(affinitySnapshot())->toEqual($first);
});

test('recalcula con los pesos nuevos de config', function (): void {
    liveActivity();
    config(['affinity.weights.upvote' => 10.0, 'affinity.weights.downvote' => 0.0, 'affinity.weights.copy' => 0.0, 'affinity.weights.favorite' => 0.0, 'affinity.weights.dismiss' => 0.0]);

    Artisan::call('app:rebuild-affinities');

    expect(array_sum(array_column(affinitySnapshot(), 0)))->toBeGreaterThan(0)
        ->and(collect(affinitySnapshot())->every(fn (array $entry): bool => $entry[0] >= 0))->toBeTrue();
});

test('sustituye lo guardado: borra puntuaciones que ya no tienen señales y no toca las favoritas', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    DB::table('user_tag_affinities')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'score' => 9.0, 'updated_at' => now()]);
    DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'created_at' => now()]);

    Artisan::call('app:rebuild-affinities');

    expect(DB::table('user_tag_affinities')->count())->toBe(0)
        ->and(DB::table('user_favorite_tags')->count())->toBe(1);
});

test('recupera las primeras copias desde events, con la fecha de la primera', function (): void {
    prepareEventPartitions();
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $copypasta->tags()->attach($tag->id);
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->byUser($member)->at(now()->subDays(3))->create();
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->byUser($member)->at(now()->subDay())->create();
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->create();

    Artisan::call('app:rebuild-affinities');

    expect(storedAffinity($member, $tag))->toBe(3.0)
        ->and(DB::table('user_copied_copypastas')->count())->toBe(1)
        ->and((string) DB::table('user_copied_copypastas')->value('created_at'))->toBe(now()->subDays(3)->toDateTimeString())
        ->and((string) DB::table('user_tag_affinities')->value('updated_at'))->toBe(now()->subDays(3)->toDateTimeString());
});

test('ignora las cuentas anonimizadas', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $copypasta->tags()->attach($tag->id);
    DB::table('copypasta_dismissals')->insert(['user_id' => $member->id, 'copypasta_id' => $copypasta->id, 'created_at' => now()]);
    $member->forceFill(['anonymized_at' => now()])->save();

    Artisan::call('app:rebuild-affinities');

    expect(DB::table('user_tag_affinities')->count())->toBe(0);
});
