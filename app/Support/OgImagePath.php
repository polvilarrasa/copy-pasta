<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Copypasta;

/**
 * Where a copy-pasta's Open Graph image lives on the disk. The name carries a hash of the template version and of the
 * text, so editing the copy-pasta or changing the template gives a new name and the platforms that cache the preview
 * fetch the new image instead of the old one.
 */
final class OgImagePath
{
    public static function for(Copypasta $copypasta): string
    {
        $hash = substr(hash('sha256', OgImageRenderer::TEMPLATE_VERSION."\0".$copypasta->title."\0".$copypasta->body), 0, 16);

        return 'og/'.$copypasta->getKey().'-'.$hash.'.png';
    }
}
