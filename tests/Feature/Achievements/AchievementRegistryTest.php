<?php

declare(strict_types=1);

use App\Enums\Achievement;
use App\Enums\AchievementMetric;
use Illuminate\Support\Facades\Lang;

test('cada logro tiene nombre y descripción en lang, y los que dan título tienen el título', function (Achievement $achievement): void {
    expect(Lang::has('achievements.items.'.$achievement->value.'.name'))->toBeTrue()
        ->and(Lang::has('achievements.items.'.$achievement->value.'.description'))->toBeTrue()
        ->and($achievement->threshold())->toBeGreaterThan(0);

    if ($achievement->titleKey() !== null) {
        expect(Lang::has('achievements.titles.'.$achievement->titleKey()))->toBeTrue();
    }
})->with(Achievement::cases());

test('no hay dos logros con la misma clave de título', function (): void {
    $titleKeys = collect(Achievement::cases())->map->titleKey()->filter()->values();

    expect($titleKeys->unique()->count())->toBe($titleKeys->count());
});

test('la descripción de cada logro de contador o derivado nombra su umbral', function (Achievement $achievement): void {
    $threshold = number_format($achievement->threshold(), 0, ',', '.');

    expect($achievement->description())->toContain($threshold);
})->with(fn (): array => collect(Achievement::cases())
    ->reject(fn (Achievement $achievement): bool => $achievement->metric()->isFlag() || $achievement->metric() === AchievementMetric::NightPublications)
    ->all());

test('los secretos son exactamente Noctámbulo y Dinamita', function (): void {
    $secrets = collect(Achievement::cases())->filter->isSecret()->values()->all();

    expect($secrets)->toBe([Achievement::NightOwl, Achievement::Dynamite]);
});
