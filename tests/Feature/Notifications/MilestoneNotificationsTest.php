<?php

declare(strict_types=1);

use App\Actions\CastVote;
use App\Actions\DetectCopypastaMilestones;
use App\Actions\RecordCopypastaCopy;
use App\Enums\MilestoneMetric;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * @return array<int, array<string, mixed>>
 */
function milestoneData(User $author): array
{
    return $author->notifications()->where('type', NotificationType::Milestone->value)->orderBy('created_at')->get()
        ->map(fn ($notification): array => $notification->data)
        ->all();
}

function castUpvotes(Copypasta $copypasta, int $count): void
{
    foreach (range(1, $count) as $_) {
        app(CastVote::class)->handle(User::factory()->create(), $copypasta, 1);
    }
}

test('el décimo upvote notifica al autor con el hito y los ids, no con el texto', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'score' => 9]);

    castUpvotes($copypasta, 1);

    expect(milestoneData($author))->toEqual([[
        'type' => 'milestone',
        'copypasta_id' => $copypasta->getKey(),
        'milestones' => [['metric' => 'upvotes', 'threshold' => 10]],
    ]]);
});

test('el décimo upvote no notifica antes de llegar al umbral', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 8, 'score' => 8]);

    castUpvotes($copypasta, 1);

    expect(milestoneData($author))->toBe([]);
});

test('cruzar los 10 upvotes, retirar un voto y volver a cruzarlos genera una sola notificación', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'score' => 9]);
    $voter = User::factory()->create();

    app(CastVote::class)->handle($voter, $copypasta, 1);
    app(CastVote::class)->handle($voter, $copypasta, 1);
    app(CastVote::class)->handle($voter, $copypasta, 1);

    expect($copypasta->refresh()->upvotes_count)->toBe(10)
        ->and(milestoneData($author))->toHaveCount(1)
        ->and(DB::table('copypasta_milestones')->count())->toBe(1);
});

test('el décimo copiado notifica el hito de copias', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 9]);

    app(RecordCopypastaCopy::class)->handle($copypasta, 'visitor-a');

    expect(milestoneData($author)[0]['milestones'])->toBe([['metric' => 'copies', 'threshold' => 10]]);
});

test('si un contador cruza varios umbrales a la vez, los registra todos y notifica solo el más alto', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 120, 'score' => 120]);

    app(DetectCopypastaMilestones::class)->handle($copypasta, MilestoneMetric::Upvotes);

    expect(DB::table('copypasta_milestones')->where('copypasta_id', $copypasta->getKey())->orderBy('threshold')->pluck('threshold')->all())->toBe([10, 100])
        ->and(milestoneData($author))->toHaveCount(1)
        ->and(milestoneData($author)[0]['milestones'])->toBe([['metric' => 'upvotes', 'threshold' => 100]]);
});

test('dos hitos del mismo copy-pasta en menos de una hora dan una notificación agrupada', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'copies_count' => 9, 'score' => 9]);

    castUpvotes($copypasta, 1);
    $this->travel(30)->minutes();
    app(RecordCopypastaCopy::class)->handle($copypasta, 'visitor-a');

    $data = milestoneData($author);

    expect($data)->toHaveCount(1)
        ->and($data[0]['milestones'])->toBe([
            ['metric' => 'upvotes', 'threshold' => 10],
            ['metric' => 'copies', 'threshold' => 10],
        ])
        ->and($author->unreadNotifications()->count())->toBe(1);
});

test('con más de una hora entre dos hitos, hay dos notificaciones', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'copies_count' => 9, 'score' => 9]);

    castUpvotes($copypasta, 1);
    $this->travel(61)->minutes();
    app(RecordCopypastaCopy::class)->handle($copypasta, 'visitor-a');

    expect(milestoneData($author))->toHaveCount(2);
});

test('un hito nuevo no se agrupa en una notificación ya leída', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'copies_count' => 9, 'score' => 9]);

    castUpvotes($copypasta, 1);
    $author->unreadNotifications()->update(['read_at' => now()]);
    app(RecordCopypastaCopy::class)->handle($copypasta, 'visitor-a');

    expect(milestoneData($author))->toHaveCount(2);
});

test('la agrupación de un copy-pasta no toca las notificaciones de otro', function (): void {
    $author = User::factory()->create();
    $first = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'score' => 9]);
    $second = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 9, 'score' => 9]);

    castUpvotes($first, 1);
    castUpvotes($second, 1);

    expect(milestoneData($author))->toHaveCount(2);
});

test('no hay hito para un copy-pasta oculto, borrado o sin publicar, y se detecta cuando vuelve a estar visible', function (): void {
    $author = User::factory()->create();
    $hidden = Copypasta::factory()->for($author, 'user')->hidden()->create(['upvotes_count' => 10, 'score' => 10]);
    $deleted = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 10, 'score' => 10]);
    $deleted->delete();
    $draft = Copypasta::factory()->for($author, 'user')->unpublished()->create(['upvotes_count' => 10, 'score' => 10]);

    foreach ([$hidden, $deleted, $draft] as $copypasta) {
        app(DetectCopypastaMilestones::class)->handle($copypasta, MilestoneMetric::Upvotes);
    }

    expect(milestoneData($author))->toBe([])
        ->and(DB::table('copypasta_milestones')->count())->toBe(0);

    $hidden->forceFill(['hidden_at' => null])->save();
    app(DetectCopypastaMilestones::class)->handle($hidden, MilestoneMetric::Upvotes);

    expect(milestoneData($author))->toHaveCount(1);
});

test('no se notifica a un autor baneado ni anonimizado y no se registra el hito', function (User $author): void {
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 10, 'score' => 10]);

    app(DetectCopypastaMilestones::class)->handle($copypasta, MilestoneMetric::Upvotes);

    expect($author->notifications()->count())->toBe(0)
        ->and(DB::table('copypasta_milestones')->count())->toBe(0);
})->with([
    'baneado' => fn () => User::factory()->banned()->create(),
    'anonimizado' => fn () => User::factory()->create(['anonymized_at' => now()]),
]);

test('con el tipo desactivado el hito se registra pero no se notifica, y no reaparece al reactivarlo', function (): void {
    $author = User::factory()->create(['notification_prefs' => ['milestone' => false]]);
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 10, 'score' => 10]);

    app(DetectCopypastaMilestones::class)->handle($copypasta, MilestoneMetric::Upvotes);

    expect($author->notifications()->count())->toBe(0)
        ->and(DB::table('copypasta_milestones')->count())->toBe(1);

    $author->forceFill(['notification_prefs' => ['milestone' => true]])->save();
    app(DetectCopypastaMilestones::class)->handle($copypasta, MilestoneMetric::Upvotes);

    expect($author->notifications()->count())->toBe(0);
});

test('retirar un voto no dispara la detección de hitos', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['upvotes_count' => 12, 'score' => 12]);
    $voter = User::factory()->create();
    DB::table('votes')->insert(['user_id' => $voter->id, 'copypasta_id' => $copypasta->id, 'value' => 1, 'created_at' => now(), 'updated_at' => now()]);

    app(CastVote::class)->handle($voter, $copypasta, 1);

    expect(milestoneData($author))->toBe([]);
});
