<?php

declare(strict_types=1);

use App\Actions\AdjustAchievementProgress;
use App\Actions\RefreshAchievementRarity;
use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use App\Models\User;
use App\Models\UserAchievement;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;

function profileOf(string $username, ?User $viewer = null): TestResponse
{
    return ($viewer === null ? test() : test()->actingAs($viewer))->get(route('profile.show', $username))->assertOk();
}

test('el dueño ve conseguidos con fecha, pendientes con barra de progreso y secretos como ???', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::FirstPaste)->create(['unlocked_at' => Carbon\Carbon::parse('2026-03-12 10:00:00')]);
    app(AdjustAchievementProgress::class)->add($owner, AchievementMetric::Published, 4);

    $response = profileOf('paco_nocturno', $owner);

    $response->assertSee('Primera pegada')
        ->assertSee('Conseguido el 12 de marzo de 2026')
        ->assertSee(__('achievements.profile.pending'))
        ->assertSee('Pegador habitual')
        ->assertSee(__('achievements.profile.progress', ['current' => 4, 'max' => 10]))
        ->assertSee('role="progressbar"', false)
        ->assertSee('aria-valuenow="4"', false)
        ->assertSee(__('achievements.profile.secret_name'))
        ->assertSee(__('achievements.profile.secret_description'))
        ->assertDontSee(Achievement::NightOwl->description())
        ->assertDontSee(Achievement::Dynamite->description());
});

test('el pendiente de cada familia es solo el siguiente escalón', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);

    $content = profileOf('paco_nocturno', $owner)->getContent();

    expect($content)->toContain(Achievement::FirstPaste->name())
        ->not->toContain(Achievement::PasteFactory->description());
});

test('un visitante no ve los pendientes ni los secretos de otro usuario', function (?User $viewer): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::FirstPaste)->create();
    app(AdjustAchievementProgress::class)->add($owner, AchievementMetric::Published, 4);

    $response = profileOf('paco_nocturno', $viewer);

    $response->assertSee('Primera pegada')
        ->assertDontSee(__('achievements.profile.pending'))
        ->assertDontSee('role="progressbar"', false)
        ->assertDontSee(__('achievements.profile.secret_name'))
        ->assertDontSee(Achievement::HabitualPaster->name());
})->with([
    'anónimo' => [null],
    'otro miembro' => fn (): array => [User::factory()->create()],
]);

test('los demás ven el número de secretos conseguidos pero no cuáles son', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::NightOwl)->create();
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::Dynamite)->create();

    $visitor = profileOf('paco_nocturno')->assertSee('2 logros secretos conseguidos')->getContent();
    $ownerView = profileOf('paco_nocturno', $owner)->getContent();

    expect($visitor)->not->toContain(Achievement::NightOwl->description())
        ->not->toContain(Achievement::Dynamite->description())
        ->and($ownerView)->toContain(Achievement::NightOwl->description())
        ->toContain(Achievement::Dynamite->description());
});

test('un logro revocado no aparece ni entre los conseguidos ni entre los pendientes', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::FirstPaste)->revoked()->create();

    $content = profileOf('paco_nocturno', $owner)->getContent();

    expect($content)->not->toContain(Achievement::FirstPaste->description());
});

test('la rareza se muestra con su porcentaje y "<1 %" por debajo del uno', function (): void {
    $common = User::factory()->create(['username' => 'paco_nocturno']);
    User::factory()->count(199)->create();
    UserAchievement::factory()->for($common)->ofAchievement(Achievement::FirstPaste)->create();
    app(RefreshAchievementRarity::class)->handle();

    profileOf('paco_nocturno')->assertSee('Lo tiene el &lt;1 % de las cuentas', false);
});

test('el título activo se marca en el logro y aparece junto al nombre', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno', 'title_key' => 'paste_factory']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::PasteFactory)->create();

    profileOf('paco_nocturno')
        ->assertSee(__('achievements.profile.active_title'))
        ->assertSee('data-test="profile-title"', false);
});

test('la sección de logros del perfil cuesta como mucho dos consultas', function (): void {
    $owner = User::factory()->create(['username' => 'paco_nocturno']);
    UserAchievement::factory()->for($owner)->ofAchievement(Achievement::FirstPaste)->create();
    app(AdjustAchievementProgress::class)->add($owner, AchievementMetric::Published, 4);

    $this->actingAs($owner);
    DB::enableQueryLog();
    $this->get(route('profile.show', 'paco_nocturno'))->assertOk();

    $achievementQueries = collect(DB::getQueryLog())
        ->filter(fn (array $query): bool => str_contains($query['query'], 'user_achievement'))
        ->count();

    expect($achievementQueries)->toBeLessThanOrEqual(2);
});
