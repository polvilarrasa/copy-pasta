<?php

declare(strict_types=1);

use App\Actions\AcceptCopypastaReports;
use App\Actions\AddToFolder;
use App\Actions\AnonymizeUser;
use App\Actions\CastVote;
use App\Actions\CreateFolder;
use App\Actions\DeleteCopypasta;
use App\Actions\DeleteFolder;
use App\Actions\DismissCopypastaReports;
use App\Actions\HideCopypasta;
use App\Actions\PublishCopypasta;
use App\Actions\RecordCopypastaCopy;
use App\Actions\RestoreCopypasta;
use App\Actions\ToggleFavorite;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Models\Copypasta;
use App\Models\Report;
use App\Models\Tag;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;

function publishForAchievements(User $author): Copypasta
{
    return app(PublishCopypasta::class)->handle($author, [
        'title' => 'Titulo de prueba',
        'body' => 'Un cuerpo suficientemente largo para publicar.',
        'is_nsfw' => false,
        'tag_ids' => [Tag::factory()->create()->getKey()],
    ]);
}

// Publicados

test('publicar suma al progreso de publicados', function (): void {
    $author = User::factory()->create();

    publishForAchievements($author);
    publishForAchievements($author);

    expect(achievementProgress($author, AchievementMetric::Published))->toBe(2);
});

test('ocultar por moderación y borrar restan de publicados, restaurar suma, y sin doble resta', function (): void {
    $author = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $hidden = publishForAchievements($author);
    $deleted = publishForAchievements($author);
    expect(achievementProgress($author, AchievementMetric::Published))->toBe(2);

    app(HideCopypasta::class)->handle($moderator, $hidden, 'Spam');
    expect(achievementProgress($author, AchievementMetric::Published))->toBe(1);

    app(DeleteCopypasta::class)->handle($author, $deleted);
    expect(achievementProgress($author, AchievementMetric::Published))->toBe(0);

    app(RestoreCopypasta::class)->handle($moderator, $hidden->refresh());
    expect(achievementProgress($author, AchievementMetric::Published))->toBe(1);

    // Borrar uno que ya estaba oculto no vuelve a restar.
    app(HideCopypasta::class)->handle($moderator, $hidden->refresh(), 'Spam');
    app(DeleteCopypasta::class)->handle($author, $hidden->refresh());
    expect(achievementProgress($author, AchievementMetric::Published))->toBe(0);
});

test('un logro ya conseguido no se pierde al ocultar o borrar el copy-pasta', function (): void {
    $author = User::factory()->create();
    $copypasta = publishForAchievements($author);
    expect(holdsAchievement($author, Achievement::FirstPaste))->toBeTrue();

    app(DeleteCopypasta::class)->handle($author, $copypasta);

    expect(achievementProgress($author, AchievementMetric::Published))->toBe(0)
        ->and(holdsAchievement($author, Achievement::FirstPaste))->toBeTrue();
});

test('solo el autor borra su copy-pasta', function (): void {
    $copypasta = Copypasta::factory()->create();

    app(DeleteCopypasta::class)->handle(User::factory()->create(), $copypasta);
})->throws(AuthorizationException::class);

// Noctámbulo

test('publicar a las 3:00, 3:30 y 3:59 cuenta como nocturno y a las 2:59 y a las 4:00 no', function (string $time, int $expected): void {
    Carbon::setTestNow(Carbon::parse($time, 'Europe/Madrid'));
    $author = User::factory()->create();

    publishForAchievements($author);

    expect(achievementProgress($author, AchievementMetric::NightPublications))->toBe($expected)
        ->and(holdsAchievement($author, Achievement::NightOwl))->toBe($expected === 1);
})->with([
    'las 3:00' => ['2026-10-12 03:00:00', 1],
    'las 3:30' => ['2026-10-12 03:30:00', 1],
    'las 3:59' => ['2026-10-12 03:59:59', 1],
    'las 2:59' => ['2026-10-12 02:59:59', 0],
    'las 4:00' => ['2026-10-12 04:00:00', 0],
]);

// Upvotes recibidos

test('un upvote de una cuenta verificada de 72 horas o más suma al autor', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(CastVote::class)->handle(User::factory()->established()->create(), $copypasta, 1);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(1)
        ->and(holdsAchievement($author, Achievement::FirstApplause))->toBeTrue();
});

test('los upvotes de una cuenta de menos de 72 horas o sin verificar no suman progreso', function (User $voter): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(CastVote::class)->handle($voter, $copypasta, 1);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(0)
        ->and(holdsAchievement($author, Achievement::FirstApplause))->toBeFalse()
        ->and($copypasta->refresh()->upvotes_count)->toBe(1);
})->with([
    'cuenta de 71 horas' => fn (): User => User::factory()->create(['created_at' => now()->subHours(71)]),
    'cuenta nueva' => fn (): User => User::factory()->create(),
    'sin verificar' => fn (): User => User::factory()->unverified()->create(['created_at' => now()->subDays(10)]),
]);

test('una cuenta justo de 72 horas sí cuenta', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    app(CastVote::class)->handle(User::factory()->create(['created_at' => now()->subHours(72)]), $copypasta, 1);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(1);
});

test('retirar un upvote que contó resta progreso pero el logro conseguido se queda', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    $voter = User::factory()->established()->create();

    app(CastVote::class)->handle($voter, $copypasta, 1);
    app(CastVote::class)->handle($voter, $copypasta, 1);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(0)
        ->and(holdsAchievement($author, Achievement::FirstApplause))->toBeTrue();
});

test('retirar un upvote que no contó no resta nada de otros upvotes', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    $young = User::factory()->create();

    app(CastVote::class)->handle(User::factory()->established()->create(), $copypasta, 1);
    app(CastVote::class)->handle($young, $copypasta, 1);
    app(CastVote::class)->handle($young, $copypasta, 1);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(1);
});

test('pasar de upvote a downvote resta, y de downvote a upvote suma', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    $voter = User::factory()->established()->create();

    app(CastVote::class)->handle($voter, $copypasta, 1);
    app(CastVote::class)->handle($voter, $copypasta, -1);
    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(0);

    app(CastVote::class)->handle($voter, $copypasta, 1);
    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(1);
});

test('anonimizar a quien votó retira sus upvotes que contaban del progreso del autor', function (): void {
    $author = User::factory()->create();
    $voter = User::factory()->established()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    app(CastVote::class)->handle($voter, $copypasta, 1);
    app(CastVote::class)->handle(User::factory()->create(), $copypasta, 1);

    app(AnonymizeUser::class)->handle($voter, null);

    expect(achievementProgress($author, AchievementMetric::UpvotesReceived))->toBe(0)
        ->and(Vote::query()->where('user_id', $voter->id)->count())->toBe(0);
});

// Votos emitidos

test('los votos emitidos son los votos activos: retirar uno lo resta y cambiarlo no suma', function (): void {
    $voter = User::factory()->create();
    [$first, $second] = Copypasta::factory()->count(2)->create();
    $vote = app(CastVote::class);

    $vote->handle($voter, $first, 1);
    $vote->handle($voter, $second, -1);
    expect(achievementProgress($voter, AchievementMetric::VotesCast))->toBe(2);

    $vote->handle($voter, $first, -1);
    expect(achievementProgress($voter, AchievementMetric::VotesCast))->toBe(2);

    $vote->handle($voter, $first, -1);
    expect(achievementProgress($voter, AchievementMetric::VotesCast))->toBe(1);
});

// Copias recibidas

test('las copias de otros suman al autor y las suyas propias no', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    $copy = app(RecordCopypastaCopy::class);

    $copy->handle($copypasta, '10.0.0.1');
    $copy->handle($copypasta, 'visitante-2', User::factory()->create());
    $copy->handle($copypasta, 'autor', $author);

    expect(achievementProgress($author, AchievementMetric::CopiesReceived))->toBe(2)
        ->and($copypasta->refresh()->copies_count)->toBe(3);
});

// Carpetas y guardados

test('crear una carpeta suma, y borrarla no resta', function (): void {
    $member = User::factory()->create();

    $folder = app(CreateFolder::class)->handle($member, 'Mis favoritas');
    expect(achievementProgress($member, AchievementMetric::FoldersCreated))->toBe(1)
        ->and(holdsAchievement($member, Achievement::FirstFolder))->toBeTrue();

    app(DeleteFolder::class)->handle($member, $folder);

    expect(achievementProgress($member, AchievementMetric::FoldersCreated))->toBe(1);
});

test('guardar en Favoritos suma y quitarlo resta; las carpetas propias no guardan', function (): void {
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    app(ToggleFavorite::class)->handle($member, $copypasta);
    expect(achievementProgress($member, AchievementMetric::Saved))->toBe(1);

    app(ToggleFavorite::class)->handle($member, $copypasta);
    expect(achievementProgress($member, AchievementMetric::Saved))->toBe(0);

    $folder = app(CreateFolder::class)->handle($member, 'Otra');
    app(AddToFolder::class)->handle($member, $folder, $copypasta);
    expect(achievementProgress($member, AchievementMetric::Saved))->toBe(0);
});

// Reportes aceptados

test('solo los reportes aceptados suman, y ni los descartados ni los pendientes', function (): void {
    $accepted = User::factory()->create();
    $dismissed = User::factory()->create();
    $pending = User::factory()->create();
    $moderator = User::factory()->moderator()->create();
    $reported = Copypasta::factory()->create();
    $other = Copypasta::factory()->create();
    Report::factory()->for($reported)->create(['reporter_id' => $accepted->id]);
    Report::factory()->for($other)->create(['reporter_id' => $dismissed->id]);
    Report::factory()->for(Copypasta::factory()->create())->create(['reporter_id' => $pending->id]);

    app(HideCopypasta::class)->handle($moderator, $reported, 'Spam');
    app(DismissCopypastaReports::class)->handle($moderator, $other);

    expect(achievementProgress($accepted, AchievementMetric::ReportsAccepted))->toBe(1)
        ->and(holdsAchievement($accepted, Achievement::Vigilante))->toBeTrue()
        ->and(achievementProgress($dismissed, AchievementMetric::ReportsAccepted))->toBe(0)
        ->and(achievementProgress($pending, AchievementMetric::ReportsAccepted))->toBe(0)
        ->and(holdsAchievement($dismissed, Achievement::Vigilante))->toBeFalse();
});

test('aceptar los reportes de un copy-pasta dos veces no suma dos veces al mismo reportero', function (): void {
    $reporter = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    Report::factory()->for($copypasta)->create(['reporter_id' => $reporter->id]);

    app(AcceptCopypastaReports::class)->handle(null, $copypasta);
    app(AcceptCopypastaReports::class)->handle(null, $copypasta);

    expect(achievementProgress($reporter, AchievementMetric::ReportsAccepted))->toBe(1);
});
