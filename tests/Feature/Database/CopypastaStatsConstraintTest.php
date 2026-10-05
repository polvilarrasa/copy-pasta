<?php

declare(strict_types=1);

use App\Models\Copypasta;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

test('borrar físicamente un copy-pasta con estadísticas diarias falla por la clave foránea', function (): void {
    $copypasta = Copypasta::factory()->create();

    DB::table('copypasta_daily_stats')->insert([
        'copypasta_id' => $copypasta->getKey(),
        'date' => now()->toDateString(),
    ]);

    expect(fn () => $copypasta->forceDelete())->toThrow(QueryException::class);
});
