<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Support\Facades\Blade;

test('el avatar muestra las iniciales del usuario', function (): void {
    $user = User::factory()->create(['username' => 'ana_perez']);

    $html = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

    expect($html)->toContain($user->initials());
});

test('el color del avatar es el mismo siempre para el mismo usuario', function (): void {
    $user = User::factory()->create();

    $first = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);
    $second = Blade::render('<x-avatar :user="$user" />', ['user' => $user]);

    expect($first)->toBe($second);
});

test('usuarios distintos reparten entre varios colores', function (): void {
    $renders = collect(range(1, 20))->map(fn (int $id) => Blade::render('<x-avatar :user="$user" />', [
        'user' => User::factory()->create(['username' => 'usuario'.$id]),
    ]));

    expect($renders->unique()->count())->toBeGreaterThan(1);
});
