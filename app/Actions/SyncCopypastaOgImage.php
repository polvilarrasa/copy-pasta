<?php

declare(strict_types=1);

namespace App\Actions;

use App\Models\Copypasta;
use App\Support\OgImagePath;
use App\Support\OgImageRenderer;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class SyncCopypastaOgImage
{
    public function __construct(private OgImageRenderer $renderer) {}

    /**
     * Makes the stored Open Graph image match the copy-pasta. A visible copy-pasta that is not adult content gets an
     * image named after its text (a new name whenever the text or the template changes), and the one it had before is
     * deleted. A hidden, deleted, unpublished or adult one has no image: the file is deleted and the generic image is
     * used. If drawing fails or runs out of time the file is left absent and the generic image is used, and the failure
     * is logged: nothing waits on it. With `force` the image is drawn again even when its name is unchanged.
     */
    public function handle(string $copypastaId, bool $force = false): void
    {
        if (! config('og.enabled')) {
            return;
        }

        $copypasta = Copypasta::withTrashed()->find($copypastaId);

        if ($copypasta === null) {
            return;
        }

        $disk = Storage::disk((string) config('og.disk'));
        $previous = $copypasta->og_image_path;

        if (! self::shouldHaveImage($copypasta)) {
            $this->forget($copypasta, $previous);

            return;
        }

        $path = OgImagePath::for($copypasta);

        if (! $force && $previous === $path) {
            return;
        }

        try {
            $png = $this->renderer->render($copypasta->title, $copypasta->body);

            if (! $disk->put($path, $png, 'public')) {
                throw new \RuntimeException('El disco no guardó la imagen.');
            }
        } catch (Throwable $exception) {
            Log::warning('No se pudo generar la imagen Open Graph; se usa la genérica', [
                'copypasta_id' => $copypasta->getKey(),
                'exception' => $exception,
            ]);

            $this->forget($copypasta, $previous);

            return;
        }

        Copypasta::withTrashed()->whereKey($copypasta->getKey())->update(['og_image_path' => $path]);

        if ($previous !== null && $previous !== $path) {
            $disk->delete($previous);
        }
    }

    /**
     * Only a visible copy-pasta that is not adult content has an image of its own.
     */
    public static function shouldHaveImage(Copypasta $copypasta): bool
    {
        return $copypasta->published_at !== null
            && $copypasta->hidden_at === null
            && $copypasta->deleted_at === null
            && ! $copypasta->is_nsfw;
    }

    private function forget(Copypasta $copypasta, ?string $previous): void
    {
        if ($previous === null) {
            return;
        }

        Storage::disk((string) config('og.disk'))->delete($previous);

        Copypasta::withTrashed()->whereKey($copypasta->getKey())->update(['og_image_path' => null]);
    }
}
