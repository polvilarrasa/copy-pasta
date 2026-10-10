<?php

declare(strict_types=1);

use App\Actions\BackfillAchievements;
use App\Actions\ComputeUserStats;
use App\Actions\PruneAttributedVisits;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Enums\EventType;
use App\Models\Copypasta;
use App\Models\TrackedEvent;
use App\Models\User;
use Illuminate\Support\Facades\DB;

beforeEach(fn () => prepareEventPartitions());

function visitWithRef(Copypasta $copypasta, ?string $ref, string $ip = '203.0.113.10', ?User $as = null, ?string $userAgent = null): void
{
    $request = test()->withServerVariables(['REMOTE_ADDR' => $ip]);

    if ($as !== null) {
        $request = $request->actingAs($as);
    }

    if ($userAgent !== null) {
        $request = $request->withHeaders(['User-Agent' => $userAgent]);
    }

    $request->get(route('copypastas.show', [$copypasta, $copypasta->slug]).($ref === null ? '' : '?ref='.$ref))->assertOk();
}

function attributedVisits(User $owner): int
{
    return achievementProgress($owner, AchievementMetric::AttributedVisits);
}

test('una visita anónima por un enlace compartido se atribuye al dueño y queda en el evento', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code);

    expect(attributedVisits($owner))->toBe(1)
        ->and(DB::table('user_daily_referrals')->where('user_id', $owner->id)->value('visits'))->toBe(1)
        ->and(TrackedEvent::query()->where('type', EventType::DetailView)->sole()->context)
        ->toMatchArray(['ref' => $owner->share_code]);
});

test('la visita del propio dueño no cuenta, haya o no sesión de otra cuenta', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code, as: $owner);

    expect(attributedVisits($owner))->toBe(0)
        ->and(DB::table('attributed_visits')->count())->toBe(0);
});

test('la visita de un bot no cuenta', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code, userAgent: 'WhatsApp/2.23.20.0 A');
    visitWithRef($copypasta, $owner->share_code, ip: '203.0.113.11', userAgent: 'Discordbot/2.0');

    expect(attributedVisits($owner))->toBe(0);
});

test('el mismo visitante el mismo día cuenta una sola vez, y otro visitante cuenta aparte', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $another = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code);
    visitWithRef($another, $owner->share_code);
    visitWithRef($copypasta, $owner->share_code);

    expect(attributedVisits($owner))->toBe(1);

    visitWithRef($copypasta, $owner->share_code, ip: '203.0.113.99');

    expect(attributedVisits($owner))->toBe(2);
});

test('un miembro con sesión cuenta una vez por día aunque cambie de IP', function (): void {
    $owner = User::factory()->create();
    $member = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code, ip: '203.0.113.1', as: $member);
    visitWithRef($copypasta, $owner->share_code, ip: '203.0.113.2', as: $member);

    expect(attributedVisits($owner))->toBe(1);
});

test('el mismo visitante el mismo día cuenta para cada dueño de enlace', function (): void {
    $first = User::factory()->create();
    $second = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $first->share_code);
    visitWithRef($copypasta, $second->share_code);

    expect(attributedVisits($first))->toBe(1)->and(attributedVisits($second))->toBe(1);
});

test('al día siguiente el mismo visitante vuelve a contar', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code);
    $this->travel(1)->day();
    visitWithRef($copypasta, $owner->share_code);

    expect(attributedVisits($owner))->toBe(2);
});

test('un código desconocido o un copy-pasta oculto no atribuyen nada', function (): void {
    $owner = User::factory()->create();
    $hidden = Copypasta::factory()->hidden()->create();

    visitWithRef(Copypasta::factory()->create(), 'zzzzzzzz');
    $this->actingAs($hidden->user)->get(route('copypastas.show', [$hidden, $hidden->slug]).'?ref='.$owner->share_code)->assertOk();

    expect(attributedVisits($owner))->toBe(0);
});

test('las páginas con ?ref= llevan canonical y og:url sin el parámetro', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();
    $clean = route('copypastas.show', [$copypasta, $copypasta->slug]);

    $this->get($clean.'?ref='.$owner->share_code.'&from=top&pos=3')
        ->assertOk()
        ->assertSee('<link rel="canonical" href="'.$clean.'">', false)
        ->assertSee('<meta property="og:url" content="'.$clean.'">', false);
});

test('la redirección al slug correcto conserva el ref', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    $this->get(route('copypastas.show', [$copypasta]).'?ref='.$owner->share_code)
        ->assertRedirect(route('copypastas.show', [$copypasta, $copypasta->slug]).'?ref='.$owner->share_code);
});

test('Mensajero, Altavoz y Megáfono se conceden con 1, 100 y 1.000 visitas atribuidas', function (): void {
    $owner = User::factory()->create();
    $copypasta = Copypasta::factory()->create();

    visitWithRef($copypasta, $owner->share_code);

    expect(holdsAchievement($owner, Achievement::Messenger))->toBeTrue()
        ->and(holdsAchievement($owner, Achievement::Loudspeaker))->toBeFalse();

    DB::table('user_achievement_progress')
        ->where('user_id', $owner->id)->where('metric', AchievementMetric::AttributedVisits->value)->update(['value' => 99]);

    visitWithRef($copypasta, $owner->share_code, ip: '203.0.113.50');

    expect(holdsAchievement($owner, Achievement::Loudspeaker))->toBeTrue()
        ->and(holdsAchievement($owner, Achievement::Megaphone))->toBeFalse()
        ->and(Achievement::Loudspeaker->titleKey())->not->toBeNull()
        ->and(Achievement::Messenger->titleKey())->toBeNull();
});

test('el backfill reconstruye la métrica desde los eventos con ref sin contar propias, repetidas ni de ocultos', function (): void {
    $owner = User::factory()->create();
    $visible = Copypasta::factory()->create();
    $hidden = Copypasta::factory()->hidden()->create();
    $ref = ['ref' => $owner->share_code];

    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext($ref)->create(['visitor_hash' => 'visitor-a']);
    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext($ref)->create(['visitor_hash' => 'visitor-a']);
    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext($ref)->at(now()->subDay())->create(['visitor_hash' => 'visitor-a']);
    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext($ref)->byUser(User::factory()->create())->create();
    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext($ref)->byUser($owner)->create();
    TrackedEvent::factory()->forCopypasta($hidden)->ofType(EventType::DetailView)->withContext($ref)->create(['visitor_hash' => 'visitor-b']);
    TrackedEvent::factory()->forCopypasta($visible)->ofType(EventType::DetailView)->withContext(['ref' => 'nadie123'])->create(['visitor_hash' => 'visitor-c']);

    app(BackfillAchievements::class)->handle();
    app(BackfillAchievements::class)->handle();

    expect(attributedVisits($owner))->toBe(3)
        ->and(DB::table('user_daily_referrals')->where('user_id', $owner->id)->sum('visits'))->toBe(3)
        ->and(holdsAchievement($owner, Achievement::Messenger))->toBeTrue()
        ->and($owner->achievements()->count())->toBe(1);
});

test('las estadísticas suman las visitas traídas en 30 días y no las más antiguas', function (): void {
    $owner = User::factory()->create();

    DB::table('user_daily_referrals')->insert([
        ['user_id' => $owner->id, 'date' => now()->toDateString(), 'visits' => 4],
        ['user_id' => $owner->id, 'date' => now()->subDays(29)->toDateString(), 'visits' => 3],
        ['user_id' => $owner->id, 'date' => now()->subDays(30)->toDateString(), 'visits' => 50],
    ]);

    expect(app(ComputeUserStats::class)->handle($owner)['referredVisits'])->toBe(7);
});

test('las marcas de días anteriores se borran y las recientes se conservan', function (): void {
    $owner = User::factory()->create();

    DB::table('attributed_visits')->insert([
        ['visitor_key' => 'a', 'owner_id' => $owner->id, 'visited_on' => now()->subDays(3)->toDateString()],
        ['visitor_key' => 'b', 'owner_id' => $owner->id, 'visited_on' => now()->toDateString()],
    ]);

    expect(app(PruneAttributedVisits::class)->handle())->toBe(1)
        ->and(DB::table('attributed_visits')->count())->toBe(1);
});
