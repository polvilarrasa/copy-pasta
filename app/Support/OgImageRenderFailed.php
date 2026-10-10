<?php

declare(strict_types=1);

namespace App\Support;

use RuntimeException;

/**
 * An Open Graph image could not be drawn: pango-view failed, ran past its time limit or produced no usable image.
 */
final class OgImageRenderFailed extends RuntimeException {}
