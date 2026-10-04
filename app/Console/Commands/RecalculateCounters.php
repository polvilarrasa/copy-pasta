<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('app:recalculate-counters')]
#[Description('Recalcula los contadores de votos, puntuación y favoritos de todos los copy-pastas desde las tablas reales')]
class RecalculateCounters extends Command
{
    public function handle(): int
    {
        $updated = DB::table('copypastas')->update([
            'upvotes_count' => DB::raw($this->countVotes(1)),
            'downvotes_count' => DB::raw($this->countVotes(-1)),
            'score' => DB::raw($this->countVotes(1).' - '.$this->countVotes(-1)),
            'favorites_count' => DB::raw(
                '(SELECT COUNT(*) FROM copypasta_folder INNER JOIN folders ON folders.id = copypasta_folder.folder_id'
                .' WHERE copypasta_folder.copypasta_id = copypastas.id AND folders.is_default = true)',
            ),
        ]);

        $this->info("Copy-pastas recalculados: {$updated}.");

        return self::SUCCESS;
    }

    private function countVotes(int $value): string
    {
        return "(SELECT COUNT(*) FROM votes WHERE votes.copypasta_id = copypastas.id AND votes.value = {$value})";
    }
}
