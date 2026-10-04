<?php

declare(strict_types=1);

use Illuminate\Console\Scheduling\Schedule;

test('la limpieza de colas está planificada cada noche', function (): void {
    $commands = collect(app(Schedule::class)->events())->map->command;

    expect($commands->contains(fn (?string $command): bool => str_contains((string) $command, 'queue:prune-batches')))->toBeTrue()
        ->and($commands->contains(fn (?string $command): bool => str_contains((string) $command, 'queue:prune-failed')))->toBeTrue();
});

test('no existe un robots.txt estático que sombree la ruta dinámica', function (): void {
    expect(file_exists(public_path('robots.txt')))->toBeFalse();
});
