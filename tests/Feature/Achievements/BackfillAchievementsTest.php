<?php

declare(strict_types=1);

use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Enums\ReportStatus;
use App\Models\Copypasta;
use App\Models\Folder;
use App\Models\Report;
use App\Models\TrackedEvent;
use App\Models\User;
use App\Models\UserAchievement;
use App\Models\Vote;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

/**
 * Existing data as it was before achievements existed: everything created through factories, with no progress rows.
 */
function seedLegacyData(): array
{
    prepareEventPartitions();

    $author = User::factory()->create();
    Copypasta::factory()->for($author, 'user')->count(3)->create();
    Copypasta::factory()->for($author, 'user')->hidden()->create();
    Copypasta::factory()->for($author, 'user')->create()->delete();
    $popular = Copypasta::factory()->for($author, 'user')->create(['copies_count' => 3]);

    $oldVoter = User::factory()->established()->create();
    $youngVoter = User::factory()->create();
    Vote::factory()->upvote()->create(['user_id' => $oldVoter->id, 'copypasta_id' => $popular->id]);
    Vote::factory()->upvote()->create(['user_id' => $youngVoter->id, 'copypasta_id' => $popular->id]);
    Vote::factory()->downvote()->create(['user_id' => User::factory()->established()->create()->id, 'copypasta_id' => $popular->id]);

    TrackedEvent::factory()->forCopypasta($popular)->ofType(EventType::Copy)->count(2)->create();
    TrackedEvent::factory()->forCopypasta($popular)->ofType(EventType::Copy)->byUser($author)->create();

    $collector = User::factory()->create();
    Folder::ensureDefaultFor($collector)->copypastas()->attach($popular->id, ['created_at' => now()]);
    Folder::factory()->for($collector)->create(['name' => 'Mía']);

    $reporter = User::factory()->create();
    Report::factory()->for(Copypasta::factory()->create())->resolved(ReportStatus::Accepted)->create(['reporter_id' => $reporter->id]);
    Report::factory()->for(Copypasta::factory()->create())->resolved(ReportStatus::Rejected)->create(['reporter_id' => $reporter->id]);
    Report::factory()->for(Copypasta::factory()->create())->create(['reporter_id' => $reporter->id]);

    $veteran = User::factory()->create(['created_at' => now()->subDays(400)]);

    return compact('author', 'oldVoter', 'youngVoter', 'collector', 'reporter', 'veteran');
}

test('el backfill calcula el progreso con las reglas de cada métrica', function (): void {
    $data = seedLegacyData();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(achievementProgress($data['author'], AchievementMetric::Published))->toBe(4)
        ->and(achievementProgress($data['author'], AchievementMetric::UpvotesReceived))->toBe(1)
        ->and(achievementProgress($data['author'], AchievementMetric::CopiesReceived))->toBe(2)
        ->and(achievementProgress($data['oldVoter'], AchievementMetric::VotesCast))->toBe(1)
        ->and(achievementProgress($data['collector'], AchievementMetric::Saved))->toBe(1)
        ->and(achievementProgress($data['collector'], AchievementMetric::FoldersCreated))->toBe(1)
        ->and(achievementProgress($data['reporter'], AchievementMetric::ReportsAccepted))->toBe(1);
});

test('el backfill concede los logros que salen del progreso, también Veterano', function (): void {
    $data = seedLegacyData();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(holdsAchievement($data['author'], Achievement::FirstPaste))->toBeTrue()
        ->and(holdsAchievement($data['author'], Achievement::HabitualPaster))->toBeFalse()
        ->and(holdsAchievement($data['author'], Achievement::FirstApplause))->toBeTrue()
        ->and(holdsAchievement($data['collector'], Achievement::FirstFolder))->toBeTrue()
        ->and(holdsAchievement($data['reporter'], Achievement::Vigilante))->toBeTrue()
        ->and(holdsAchievement($data['veteran'], Achievement::Veteran))->toBeTrue()
        ->and(holdsAchievement($data['youngVoter'], Achievement::Veteran))->toBeFalse();
});

test('el backfill no crea ninguna notificación', function (): void {
    seedLegacyData();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(UserAchievement::query()->count())->toBeGreaterThan(0)
        ->and(DB::table('notifications')->count())->toBe(0);
});

test('ejecutar el backfill dos veces no cambia nada', function (): void {
    seedLegacyData();

    Artisan::call('app:backfill-achievements', ['--force' => true]);
    $snapshot = fn (): array => [
        DB::table('user_achievement_progress')->orderBy('user_id')->orderBy('metric')->get()->toArray(),
        DB::table('user_achievements')->orderBy('user_id')->orderBy('achievement_key')->get()->toArray(),
        DB::table('votes')->orderBy('id')->pluck('counts_for_achievements')->all(),
    ];
    $first = $snapshot();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect($snapshot())->toEqual($first);
});

test('el backfill no vuelve a conceder un logro revocado', function (): void {
    $data = seedLegacyData();
    UserAchievement::factory()->for($data['author'])->ofAchievement(Achievement::FirstPaste)->revoked()->create();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(holdsAchievement($data['author'], Achievement::FirstPaste))->toBeFalse()
        ->and(UserAchievement::query()->where('user_id', $data['author']->id)->where('achievement_key', 'first_paste')->count())->toBe(1);
});

test('el backfill no concede nada a cuentas baneadas o anonimizadas', function (): void {
    $banned = User::factory()->banned()->create();
    Copypasta::factory()->for($banned, 'user')->create();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(achievementProgress($banned, AchievementMetric::Published))->toBe(1)
        ->and(holdsAchievement($banned, Achievement::FirstPaste))->toBeFalse();
});

test('el backfill detecta Dinamita en la ventana de 24 horas y no en 48', function (): void {
    prepareEventPartitions();
    $dynamite = Copypasta::factory()->create(['copies_count' => 100]);
    $spread = Copypasta::factory()->create(['copies_count' => 100]);
    TrackedEvent::factory()->forCopypasta($dynamite)->ofType(EventType::Copy)->count(100)->at(now()->subHours(5))->create();
    TrackedEvent::factory()->forCopypasta($spread)->ofType(EventType::Copy)->count(50)->at(now()->subHours(40))->create();
    TrackedEvent::factory()->forCopypasta($spread)->ofType(EventType::Copy)->count(50)->at(now()->subHours(5))->create();

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect(holdsAchievement($dynamite->user, Achievement::Dynamite))->toBeTrue()
        ->and(holdsAchievement($spread->user, Achievement::Dynamite))->toBeFalse();
});

test('el backfill clasifica los upvotes antiguos por la edad de la cuenta al votar', function (): void {
    $copypasta = Copypasta::factory()->create();
    $verifiedOld = Vote::factory()->upvote()->create([
        'user_id' => User::factory()->create(['created_at' => now()->subDays(10), 'email_verified_at' => now()->subDays(10)])->id,
        'copypasta_id' => $copypasta->id,
        'updated_at' => now()->subDays(2),
    ]);
    $tooYoungAtTheTime = Vote::factory()->upvote()->create([
        'user_id' => User::factory()->create(['created_at' => now()->subDays(3), 'email_verified_at' => now()->subDays(3)])->id,
        'copypasta_id' => $copypasta->id,
        'updated_at' => now()->subDays(2),
    ]);

    Artisan::call('app:backfill-achievements', ['--force' => true]);

    expect($verifiedOld->refresh()->counts_for_achievements)->toBeTrue()
        ->and($tooYoungAtTheTime->refresh()->counts_for_achievements)->toBeFalse();
});

test('el comando se niega a correr si la app no está en mantenimiento, salvo con --force', function (): void {
    $data = seedLegacyData();

    $this->artisan('app:backfill-achievements')->assertFailed();
    expect(DB::table('user_achievement_progress')->count())->toBe(0);

    $this->artisan('app:backfill-achievements', ['--force' => true])->assertSuccessful();
    expect(achievementProgress($data['author'], AchievementMetric::Published))->toBe(4);
});

test('el comando corre sin --force con la app en mantenimiento', function (): void {
    $data = seedLegacyData();
    Artisan::call('down');

    try {
        $this->artisan('app:backfill-achievements')->assertSuccessful();
    } finally {
        Artisan::call('up');
    }

    expect(achievementProgress($data['author'], AchievementMetric::Published))->toBe(4);
});
