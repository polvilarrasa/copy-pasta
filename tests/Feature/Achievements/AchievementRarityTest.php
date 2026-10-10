<?php

declare(strict_types=1);

use App\Actions\RefreshAchievementRarity;
use App\Enums\Achievement;
use App\Models\User;
use App\Models\UserAchievement;
use App\Support\AchievementRarity;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Support\Facades\Artisan;

test('la rareza es el porcentaje de cuentas activas que tienen el logro', function (): void {
    $holders = User::factory()->count(2)->create();
    User::factory()->count(2)->create();
    $holders->each(fn (User $user) => UserAchievement::factory()->for($user)->ofAchievement(Achievement::FirstPaste)->create());

    $percentages = app(RefreshAchievementRarity::class)->handle();

    expect($percentages['first_paste'])->toBe(50.0)
        ->and($percentages['dynamite'])->toBe(0.0)
        ->and(app(AchievementRarity::class)->label(Achievement::FirstPaste))->toBe('50 %');
});

test('las cuentas baneadas y anonimizadas no cuentan ni como base ni como titulares, y los revocados tampoco', function (): void {
    $active = User::factory()->create();
    $banned = User::factory()->banned()->create();
    $anonymized = User::factory()->create(['anonymized_at' => now()]);
    $revoked = User::factory()->create();
    foreach ([$active, $banned, $anonymized] as $user) {
        UserAchievement::factory()->for($user)->ofAchievement(Achievement::Viral)->create();
    }
    UserAchievement::factory()->for($revoked)->ofAchievement(Achievement::Viral)->revoked()->create();

    $percentages = app(RefreshAchievementRarity::class)->handle();

    // Activas: $active y $revoked. Titular activo y no revocado: solo $active.
    expect($percentages['viral'])->toBe(50.0);
});

test('por debajo del 1 % se muestra "<1 %"', function (): void {
    $holder = User::factory()->create();
    User::factory()->count(199)->create();
    UserAchievement::factory()->for($holder)->ofAchievement(Achievement::Legend)->create();

    app(RefreshAchievementRarity::class)->handle();

    expect(app(AchievementRarity::class)->label(Achievement::Legend))->toBe('<1 %');
});

test('mientras el job diario no ha corrido no hay etiqueta de rareza', function (): void {
    expect(app(AchievementRarity::class)->label(Achievement::FirstPaste))->toBeNull();
});

test('el comando recalcula la rareza y los tres jobs están planificados', function (): void {
    User::factory()->create();

    Artisan::call('achievements:refresh-rarity');

    expect(app(AchievementRarity::class)->all())->toHaveKey('first_paste');

    $commands = collect(app(Schedule::class)->events())->map->command->implode(' ');

    expect($commands)->toContain('achievements:grant-trending')
        ->toContain('achievements:grant-veterans')
        ->toContain('achievements:refresh-rarity');
});
