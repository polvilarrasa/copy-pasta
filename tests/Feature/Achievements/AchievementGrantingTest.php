<?php

declare(strict_types=1);

use App\Actions\AdjustAchievementProgress;
use App\Actions\BanUser;
use App\Actions\CastVote;
use App\Actions\EvaluateUserAchievements;
use App\Actions\GrantAchievement;
use App\Actions\GrantTrendingAchievements;
use App\Actions\RecordCopypastaCopy;
use App\Actions\ToggleFavorite;
use App\Actions\UnbanUser;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Enums\NotificationType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Models\UserAchievement;
use App\Support\NotificationPresenter;
use Illuminate\Support\Facades\Artisan;

// Una prueba por familia

test('Creador: 10 copy-pastas visibles conceden Pegador habitual y los anteriores', function (): void {
    $author = User::factory()->create();

    app(AdjustAchievementProgress::class)->add($author, AchievementMetric::Published, 10);
    app(EvaluateUserAchievements::class)->handle($author, [AchievementMetric::Published]);

    expect(holdsAchievement($author, Achievement::FirstPaste))->toBeTrue()
        ->and(holdsAchievement($author, Achievement::HabitualPaster))->toBeTrue()
        ->and(holdsAchievement($author, Achievement::PasteFactory))->toBeFalse();
});

test('Popularidad: 10 upvotes de cuentas válidas conceden Bien recibido', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();

    User::factory()->established()->count(10)->create()
        ->each(fn (User $voter) => app(CastVote::class)->handle($voter, $copypasta, 1));

    expect(holdsAchievement($author, Achievement::WellReceived))->toBeTrue()
        ->and(holdsAchievement($author, Achievement::PublicFavorite))->toBeFalse();
});

test('Copias: 10 copias de otras personas conceden Copiado, pero las del autor no', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create();
    $copy = app(RecordCopypastaCopy::class);

    foreach (range(1, 9) as $visitor) {
        $copy->handle($copypasta, "visitante-{$visitor}");
    }
    $copy->handle($copypasta, 'autor', $author);
    expect(holdsAchievement($author, Achievement::Copied))->toBeFalse();

    $copy->handle($copypasta, 'visitante-10');
    expect(holdsAchievement($author, Achievement::Copied))->toBeTrue();
});

test('Tendencia: los autores del top 10 semanal reciben el logro y el undécimo no', function (): void {
    $authors = User::factory()->count(11)->create();
    $authors->each(fn (User $author, int $index) => Copypasta::factory()->for($author, 'user')->create(['score' => 100 - $index]));

    app(GrantTrendingAchievements::class)->handle();

    expect($authors->filter(fn (User $author): bool => holdsAchievement($author, Achievement::Trending)))->toHaveCount(10)
        ->and(holdsAchievement($authors->last(), Achievement::Trending))->toBeFalse();
});

test('Tendencia: ignora copy-pastas con score cero, NSFW, ocultos y de hace más de una semana', function (): void {
    $zero = Copypasta::factory()->create(['score' => 0]);
    $nsfw = Copypasta::factory()->nsfw()->create(['score' => 50]);
    $hidden = Copypasta::factory()->hidden()->create(['score' => 50]);
    $old = Copypasta::factory()->publishedDaysAgo(8)->create(['score' => 50]);

    app(GrantTrendingAchievements::class)->handle();

    foreach ([$zero, $nsfw, $hidden, $old] as $copypasta) {
        expect(holdsAchievement($copypasta->user, Achievement::Trending))->toBeFalse();
    }
});

test('Coleccionista: guardar 50 copy-pastas concede el logro', function (): void {
    $member = User::factory()->create();
    app(AdjustAchievementProgress::class)->add($member, AchievementMetric::Saved, 49);

    app(ToggleFavorite::class)->handle($member, Copypasta::factory()->create());

    expect(holdsAchievement($member, Achievement::Collector))->toBeTrue();
});

test('Guardián: 10 reportes aceptados conceden Guardián y no Centinela', function (): void {
    $reporter = User::factory()->create();

    app(AdjustAchievementProgress::class)->add($reporter, AchievementMetric::ReportsAccepted, 10);
    app(EvaluateUserAchievements::class)->handle($reporter, [AchievementMetric::ReportsAccepted]);

    expect(holdsAchievement($reporter, Achievement::Guardian))->toBeTrue()
        ->and(holdsAchievement($reporter, Achievement::Sentinel))->toBeFalse();
});

test('Votante: el voto número 100 concede Crítico', function (): void {
    $voter = User::factory()->create();
    app(AdjustAchievementProgress::class)->add($voter, AchievementMetric::VotesCast, 99);

    app(CastVote::class)->handle($voter, Copypasta::factory()->create(), 1);

    expect(holdsAchievement($voter, Achievement::Critic))->toBeTrue();
});

test('Veterano: el job diario lo concede al cumplir 365 días de cuenta y no antes', function (): void {
    $veteran = User::factory()->create(['created_at' => now()->subDays(366)]);
    $recent = User::factory()->create(['created_at' => now()->subDays(364)]);
    $bannedVeteran = User::factory()->banned()->create(['created_at' => now()->subDays(400)]);

    Artisan::call('achievements:grant-veterans');

    expect(holdsAchievement($veteran, Achievement::Veteran))->toBeTrue()
        ->and(holdsAchievement($recent, Achievement::Veteran))->toBeFalse()
        ->and(holdsAchievement($bannedVeteran, Achievement::Veteran))->toBeFalse();
});

test('Secreto Dinamita: 100 copias de otras personas en 24 horas lo conceden', function (): void {
    prepareEventPartitions();
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 99]);
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->count(99)->at(now()->subHours(3))->create();

    app(RecordCopypastaCopy::class)->handle($copypasta, '10.0.0.1');

    expect(holdsAchievement($author, Achievement::Dynamite))->toBeTrue();
});

test('Dinamita no se concede con 100 copias repartidas en 48 horas', function (): void {
    prepareEventPartitions();
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 99]);
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->count(50)->at(now()->subHours(40))->create();
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->count(49)->at(now()->subHours(3))->create();

    app(RecordCopypastaCopy::class)->handle($copypasta, '10.0.0.1');

    expect(holdsAchievement($author, Achievement::Dynamite))->toBeFalse()
        ->and(achievementProgress($author, AchievementMetric::Dynamite))->toBe(0);
});

test('Dinamita no cuenta las copias del propio autor', function (): void {
    prepareEventPartitions();
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 99]);
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->byUser($author)->count(50)->at(now()->subHours(3))->create();
    TrackedEvent::factory()->forCopypasta($copypasta)->ofType(EventType::Copy)->count(49)->at(now()->subHours(3))->create();

    app(RecordCopypastaCopy::class)->handle($copypasta, '10.0.0.1');

    expect(holdsAchievement($author, Achievement::Dynamite))->toBeFalse();
});

test('con menos de 100 copias en total no se consulta nada y no se concede Dinamita', function (): void {
    $author = User::factory()->create();
    $copypasta = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 10]);

    app(RecordCopypastaCopy::class)->handle($copypasta, '10.0.0.1');

    expect(holdsAchievement($author, Achievement::Dynamite))->toBeFalse();
});

// Concesión idempotente y notificación

test('conceder el mismo logro dos veces inserta una sola fila y notifica una sola vez', function (): void {
    $member = User::factory()->create();
    $grant = app(GrantAchievement::class);

    expect($grant->handle($member, Achievement::FirstPaste))->toBeTrue()
        ->and($grant->handle($member, Achievement::FirstPaste))->toBeFalse()
        ->and(UserAchievement::query()->where('user_id', $member->id)->count())->toBe(1)
        ->and($member->notifications()->where('type', NotificationType::AchievementUnlocked->value)->count())->toBe(1);
});

test('dos evaluaciones del mismo usuario no notifican dos veces', function (): void {
    $member = User::factory()->create();
    app(AdjustAchievementProgress::class)->add($member, AchievementMetric::Published, 1);

    app(EvaluateUserAchievements::class)->handle($member, [AchievementMetric::Published]);
    app(EvaluateUserAchievements::class)->handle($member, [AchievementMetric::Published]);

    expect($member->notifications()->where('type', NotificationType::AchievementUnlocked->value)->count())->toBe(1);
});

test('si otra evaluación ya insertó el logro entre la lectura y la concesión, no se notifica', function (): void {
    $member = User::factory()->create();
    // La fila ya existe aunque esta llamada no la haya visto: lo que garantiza insertOrIgnore.
    UserAchievement::factory()->for($member)->ofAchievement(Achievement::FirstPaste)->create();

    $inserted = app(GrantAchievement::class)->handle($member, Achievement::FirstPaste);

    expect($inserted)->toBeFalse()
        ->and($member->notifications()->count())->toBe(0);
});

test('la notificación del logro respeta la preferencia del usuario pero el logro se concede', function (): void {
    $member = User::factory()->create(['notification_prefs' => ['achievement_unlocked' => false]]);

    app(GrantAchievement::class)->handle($member, Achievement::FirstPaste);

    expect(holdsAchievement($member, Achievement::FirstPaste))->toBeTrue()
        ->and($member->notifications()->count())->toBe(0);
});

test('el texto de la notificación nombra el logro', function (): void {
    $member = User::factory()->create(['username' => 'paco_nocturno']);
    app(GrantAchievement::class)->handle($member, Achievement::FirstPaste);
    $this->actingAs($member);

    $item = app(NotificationPresenter::class)
        ->present($member->notifications()->get())
        ->sole();

    expect($item->text)->toBe('Has conseguido el logro «Primera pegada».')
        ->and($item->icon)->toBe('clipboard-paste')
        ->and($item->url)->toBe(route('profile.show', 'paco_nocturno').'#logros');
});

// Cuentas baneadas y desbaneo

test('una cuenta baneada no se evalúa pero su progreso sigue sumando', function (): void {
    $banned = User::factory()->banned()->create();

    app(AdjustAchievementProgress::class)->add($banned, AchievementMetric::Published, 1);
    $granted = app(EvaluateUserAchievements::class)->handle($banned, [AchievementMetric::Published]);

    expect($granted)->toBe([])
        ->and(holdsAchievement($banned, Achievement::FirstPaste))->toBeFalse()
        ->and(achievementProgress($banned, AchievementMetric::Published))->toBe(1);
});

test('una cuenta anonimizada no se evalúa', function (): void {
    $anonymized = User::factory()->create(['anonymized_at' => now()]);

    app(AdjustAchievementProgress::class)->add($anonymized, AchievementMetric::Published, 1);

    expect(app(EvaluateUserAchievements::class)->handle($anonymized, [AchievementMetric::Published]))->toBe([]);
});

test('al desbanear se evalúa la cuenta y se conceden los logros cumplidos durante el baneo', function (): void {
    $admin = User::factory()->admin()->withTwoFactor()->create();
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->for($member, 'user')->create();

    app(BanUser::class)->handle($admin, $member, 'Spam');
    app(AdjustAchievementProgress::class)->add($member, AchievementMetric::Published, 1);
    app(CastVote::class)->handle(User::factory()->established()->create(), $copypasta, 1);
    expect(holdsAchievement($member, Achievement::FirstPaste))->toBeFalse()
        ->and(holdsAchievement($member, Achievement::FirstApplause))->toBeFalse();

    app(UnbanUser::class)->handle($admin, $member->refresh());

    expect(holdsAchievement($member, Achievement::FirstPaste))->toBeTrue()
        ->and(holdsAchievement($member, Achievement::FirstApplause))->toBeTrue();
});
