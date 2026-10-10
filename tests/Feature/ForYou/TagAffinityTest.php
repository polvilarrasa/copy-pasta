<?php

declare(strict_types=1);

use App\Actions\AddToFolder;
use App\Actions\AdjustTagAffinity;
use App\Actions\AnonymizeUser;
use App\Actions\CastVote;
use App\Actions\CreateFolder;
use App\Actions\DismissCopypasta;
use App\Actions\RecordCopypastaCopy;
use App\Actions\RemoveFromFolder;
use App\Actions\ToggleFavorite;
use App\Actions\UndoDismissCopypasta;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\Tag;
use App\Models\User;
use App\Support\TagAffinities;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * A copy-pasta of another author, carrying the given tags.
 */
function taggedCopypasta(Tag ...$tags): Copypasta
{
    $copypasta = Copypasta::factory()->create();
    $copypasta->tags()->attach(array_map(fn (Tag $tag): int => $tag->id, $tags));

    return $copypasta;
}

beforeEach(function (): void {
    Carbon::setTestNow(Carbon::parse('2026-10-12 12:00:00'));
});

test('copiar suma 3 a todas las etiquetas del copy-pasta, solo la primera copia de cada uno', function (): void {
    [$humor, $chat] = Tag::factory()->count(2)->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($humor, $chat);

    foreach (['a', 'b', 'c', 'd', 'e'] as $visitor) {
        app(RecordCopypastaCopy::class)->handle($copypasta, $visitor, $member);
    }

    expect(storedAffinity($member, $humor))->toBe(3.0)
        ->and(storedAffinity($member, $chat))->toBe(3.0)
        ->and($copypasta->refresh()->copies_count)->toBe(5)
        ->and(DB::table('user_copied_copypastas')->where('user_id', $member->id)->count())->toBe(1);
});

test('copiar otro copy-pasta de la misma etiqueta suma otra vez, y copiar sin sesión no suma', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();

    app(RecordCopypastaCopy::class)->handle(taggedCopypasta($tag), 'a', $member);
    app(RecordCopypastaCopy::class)->handle(taggedCopypasta($tag), 'b', $member);
    app(RecordCopypastaCopy::class)->handle(taggedCopypasta($tag), 'c');

    expect(storedAffinity($member, $tag))->toBe(6.0)
        ->and(DB::table('user_tag_affinities')->count())->toBe(1);
});

test('guardar en Favoritos suma 3 y quitarlo resta 3', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);

    app(ToggleFavorite::class)->handle($member, $copypasta);
    expect(storedAffinity($member, $tag))->toBe(3.0);

    app(ToggleFavorite::class)->handle($member, $copypasta);
    expect(storedAffinity($member, $tag))->toBe(0.0);
});

test('añadir a la carpeta de Favoritos suma y quitarla resta, pero cualquier otra carpeta no cambia la afinidad', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);
    $favorites = Folder::ensureDefaultFor($member);
    $other = app(CreateFolder::class)->handle($member, 'Para el grupo');

    app(AddToFolder::class)->handle($member, $other, $copypasta);
    expect(storedAffinity($member, $tag))->toBeNull();

    app(AddToFolder::class)->handle($member, $favorites, $copypasta);
    expect(storedAffinity($member, $tag))->toBe(3.0);

    app(RemoveFromFolder::class)->handle($member, $other, $copypasta);
    expect(storedAffinity($member, $tag))->toBe(3.0);

    app(RemoveFromFolder::class)->handle($member, $favorites, $copypasta);
    expect(storedAffinity($member, $tag))->toBe(0.0);
});

test('un upvote suma 1, un downvote resta 2, y cambiar o retirar el voto aplica el delta inverso', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);
    $vote = app(CastVote::class);

    $vote->handle($member, $copypasta, 1);
    expect(storedAffinity($member, $tag))->toBe(1.0);

    $vote->handle($member, $copypasta, -1);
    expect(storedAffinity($member, $tag))->toBe(-2.0);

    $vote->handle($member, $copypasta, -1);
    expect(storedAffinity($member, $tag))->toBe(0.0);
});

test('un upvote y retirarlo en el mismo instante deja la afinidad como estaba', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);
    DB::table('user_tag_affinities')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'score' => 4.0, 'updated_at' => now()]);

    app(CastVote::class)->handle($member, $copypasta, 1);
    app(CastVote::class)->handle($member, $copypasta, 1);

    expect(storedAffinity($member, $tag))->toBe(4.0);
});

test('con el paso de los días, retirar un upvote solo deja la diferencia del decaimiento', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);

    app(CastVote::class)->handle($member, $copypasta, 1);
    Carbon::setTestNow(now()->addDays(30));
    app(CastVote::class)->handle($member, $copypasta, 1);

    // 1 × 0,5 (30 días, la vida media) − 1
    expect(storedAffinity($member, $tag))->toEqualWithDelta(-0.5, 0.0001);
});

test('el decaimiento es perezoso: al actualizar se aplica antes del delta y al leer se aplica igual', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    DB::table('user_tag_affinities')->insert([
        'user_id' => $member->id,
        'tag_id' => $tag->id,
        'score' => 8.0,
        'updated_at' => now()->subDays(30),
    ]);

    expect(app(TagAffinities::class)->effective($member)[$tag->id])->toEqualWithDelta(4.0, 0.0001);

    app(AdjustTagAffinity::class)->handle($member, taggedCopypasta($tag), 3.0);

    expect(storedAffinity($member, $tag))->toEqualWithDelta(7.0, 0.0001);
    expect(DB::table('user_tag_affinities')->where('user_id', $member->id)->value('updated_at'))->toBe(now()->toDateTimeString());
});

test('las etiquetas favoritas suman +5 al leer, no decaen y no se mezclan con el score guardado', function (): void {
    $favorite = Tag::factory()->create();
    $scored = Tag::factory()->create();
    $member = User::factory()->create();
    DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $favorite->id, 'created_at' => now()->subYear()]);
    DB::table('user_tag_affinities')->insert(['user_id' => $member->id, 'tag_id' => $scored->id, 'score' => 2.0, 'updated_at' => now()]);
    DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $scored->id, 'created_at' => now()]);

    $effective = app(TagAffinities::class)->effective($member);

    expect($effective[$favorite->id])->toBe(5.0)
        ->and($effective[$scored->id])->toBe(7.0)
        ->and(storedAffinity($member, $favorite))->toBeNull();
});

test('"no me interesa" resta 3 y deshacer lo devuelve', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);

    expect(app(DismissCopypasta::class)->handle($member, $copypasta))->toBeTrue()
        ->and(app(DismissCopypasta::class)->handle($member, $copypasta))->toBeFalse()
        ->and(storedAffinity($member, $tag))->toBe(-3.0);

    expect(app(UndoDismissCopypasta::class)->handle($member, $copypasta))->toBeTrue()
        ->and(app(UndoDismissCopypasta::class)->handle($member, $copypasta))->toBeFalse()
        ->and(storedAffinity($member, $tag))->toBe(0.0)
        ->and(DB::table('copypasta_dismissals')->count())->toBe(0);
});

test('no se puede descartar un copy-pasta propio ni uno oculto', function (): void {
    $member = User::factory()->create();

    expect(fn () => app(DismissCopypasta::class)->handle($member, Copypasta::factory()->for($member, 'user')->create()))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => app(DismissCopypasta::class)->handle($member, Copypasta::factory()->hidden()->create()))
        ->toThrow(AuthorizationException::class);
});

test('los pesos salen de config y cambiarlos cambia la afinidad', function (): void {
    config(['affinity.weights.upvote' => 2.5]);
    $tag = Tag::factory()->create();
    $member = User::factory()->create();

    app(CastVote::class)->handle($member, taggedCopypasta($tag), 1);

    expect(storedAffinity($member, $tag))->toBe(2.5);
});

test('anonimizar una cuenta borra su afinidad, favoritas, copias y descartes', function (): void {
    $tag = Tag::factory()->create();
    $member = User::factory()->create();
    $copypasta = taggedCopypasta($tag);
    app(RecordCopypastaCopy::class)->handle($copypasta, 'a', $member);
    app(DismissCopypasta::class)->handle($member, taggedCopypasta($tag));
    DB::table('user_favorite_tags')->insert(['user_id' => $member->id, 'tag_id' => $tag->id, 'created_at' => now()]);

    app(AnonymizeUser::class)->handle($member, null);

    foreach (['user_tag_affinities', 'user_favorite_tags', 'user_copied_copypastas', 'copypasta_dismissals'] as $table) {
        expect(DB::table($table)->where('user_id', $member->id)->count())->toBe(0);
    }
});
