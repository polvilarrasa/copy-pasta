<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Actions\SyncCopypastaOgImage;
use App\Jobs\SyncCopypastaOgImageJob;
use App\Models\Copypasta;
use App\Support\OgImagePath;
use App\Support\OgImageRenderer;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

#[Signature('app:generate-og-images {--force : Regenerar también las imágenes que ya están al día} {--generic : Escribir solo la imagen genérica en public/images/og-generic.png}')]
#[Description('Encola por lotes la generación de las imágenes Open Graph que faltan o que han cambiado de plantilla')]
class GenerateOgImages extends Command
{
    private const BATCH_SIZE = 200;

    public function handle(OgImageRenderer $renderer): int
    {
        if ($this->option('generic')) {
            $path = public_path('images/og-generic.png');
            @mkdir(dirname($path), 0775, true);
            file_put_contents($path, $renderer->renderGeneric());

            $this->info('Imagen genérica escrita en '.$path.'.');

            return self::SUCCESS;
        }

        $queued = 0;

        Copypasta::query()
            ->visible()
            ->where('is_nsfw', false)
            ->select(['id', 'title', 'body', 'og_image_path', 'published_at', 'hidden_at', 'deleted_at', 'is_nsfw'])
            ->chunkById(self::BATCH_SIZE, function ($copypastas) use (&$queued): void {
                $jobs = $copypastas
                    ->filter(fn (Copypasta $copypasta): bool => SyncCopypastaOgImage::shouldHaveImage($copypasta)
                        && ($this->option('force') || $copypasta->og_image_path !== OgImagePath::for($copypasta)))
                    ->map(fn (Copypasta $copypasta): SyncCopypastaOgImageJob => new SyncCopypastaOgImageJob($copypasta->getKey(), (bool) $this->option('force')))
                    ->values()
                    ->all();

                if ($jobs === []) {
                    return;
                }

                Bus::batch($jobs)->name('og-images')->allowFailures()->dispatch();
                $queued += count($jobs);
            }, 'id');

        $this->info('Imágenes encoladas: '.$queued.'.');

        return self::SUCCESS;
    }
}
