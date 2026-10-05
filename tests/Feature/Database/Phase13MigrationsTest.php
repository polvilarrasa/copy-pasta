<?php

declare(strict_types=1);

use App\Models\Copypasta;
use Illuminate\Support\Facades\DB;

function runPhase13Migration(string $name, string $direction = 'up'): void
{
    $migration = require glob(database_path("migrations/*_{$name}.php"))[0];

    $migration->{$direction}();
}

test('la migración de share_code da un código distinto a cada usuario existente', function (): void {
    runPhase13Migration('add_identity_columns_to_users_table', 'down');

    DB::table('users')->insert(collect(['ana', 'bea', 'carla'])->map(fn (string $username) => [
        'username' => $username,
        'email' => $username.'@example.test',
        'password' => 'x',
        'role' => 'user',
        'created_at' => now(),
        'updated_at' => now(),
    ])->all());

    runPhase13Migration('add_identity_columns_to_users_table');

    $codes = DB::table('users')->pluck('share_code');

    expect($codes)->toHaveCount(3)
        ->and($codes->unique())->toHaveCount(3)
        ->and($codes->every(fn (string $code): bool => preg_match('/^[A-Za-z0-9]{8}$/', $code) === 1))->toBeTrue();
});

test('la migración de títulos quita los caracteres de dirección y recalcula el slug', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Título normal']);

    DB::table('copypastas')->where('id', $copypasta->getKey())->update([
        'title' => "Truco\u{202E}oculto",
        'slug' => 'truco-oculto',
    ]);

    runPhase13Migration('clean_copypasta_titles');

    expect($copypasta->refresh())
        ->title->toBe('Trucooculto')
        ->slug->toBe('trucooculto');
});

test('la migración de títulos deja el ULID como slug si el título no tiene letras', function (): void {
    $copypasta = Copypasta::factory()->create(['title' => 'Título normal']);

    DB::table('copypastas')->where('id', $copypasta->getKey())->update([
        'title' => "🔥\u{200B}",
        'slug' => 'otro',
    ]);

    runPhase13Migration('clean_copypasta_titles');

    expect($copypasta->refresh()->slug)->toBe($copypasta->getKey());
});
