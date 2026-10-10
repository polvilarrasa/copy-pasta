<?php

declare(strict_types=1);

namespace App\Support;

use GdImage;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

/**
 * Draws the 1200 × 630 Open Graph card in the "Medianoche" style of the design.
 *
 * Every text block is rendered by `pango-view` (Pango, HarfBuzz and fontconfig), which wraps lines, orders right to
 * left text, shapes Arabic and falls back to the emoji and CJK fonts by itself. The user's text always reaches it as
 * plain text in a temporary file, never as markup and never inside a shell string, and every process has a time
 * limit. GD only composes: the card is drawn at twice the size and scaled down, so its edges are smooth, and the
 * text blocks are laid on top of it.
 *
 * The body is cut at the end of its block with a fade, which also contains whatever a text such as zalgo draws above
 * or below its line. Change TEMPLATE_VERSION whenever the drawing changes: it is part of every image name.
 */
class OgImageRenderer
{
    public const TEMPLATE_VERSION = 1;

    public const WIDTH = 1200;

    public const HEIGHT = 630;

    private const COLOR_BG = '#0E0D1A';

    private const COLOR_SURFACE = '#17162A';

    private const COLOR_BORDER = '#2A2847';

    private const COLOR_INK = '#F1F0FF';

    private const COLOR_MUTED = '#A5A2D0';

    private const COLOR_ACCENT = '#C6F432';

    private const PAD_X = 88;

    private const TEXT_WIDTH = self::WIDTH - self::PAD_X * 2;

    private const TITLE_TOP = 82;

    private const TITLE_SIZE = 56;

    private const TITLE_LINES = 2;

    private const BODY_SIZE = 32;

    private const BODY_BOTTOM = 476;

    private const BODY_GAP = 26;

    private const FADE_HEIGHT = 56;

    private const PARAGRAPH_GAP = 12;

    private const BRAND_TOP = 506;

    private const MAX_TITLE_CHARACTERS = 200;

    private const MAX_BODY_CHARACTERS = 700;

    private const MAX_BODY_PARAGRAPHS = 12;

    private const MAX_LINES_PER_PARAGRAPH = 6;

    private const MAX_BLOCK_PIXELS = 12_000_000;

    private const FONT_FAMILIES = 'Bricolage Grotesque,Noto Sans Arabic,Noto Sans Hebrew,Noto Sans CJK JP';

    public function __construct(
        private readonly string $pangoView = 'pango-view',
        private readonly int $timeout = 10,
        private readonly ?string $fontsDirectory = null,
        private readonly ?string $runtimeDirectory = null,
    ) {}

    /**
     * The PNG of a copy-pasta's card.
     *
     * @throws OgImageRenderFailed
     */
    public function render(string $title, string $body): string
    {
        $title = trim((string) preg_replace('/\s+/u', ' ', UnicodeText::forRendering($title)));
        $title = mb_substr($title, 0, self::MAX_TITLE_CHARACTERS);

        $body = UnicodeText::forRendering($body);
        $truncated = mb_strlen($body) > self::MAX_BODY_CHARACTERS;
        $body = mb_substr($body, 0, self::MAX_BODY_CHARACTERS);

        $canvas = $this->card();

        $titleBlock = $this->textBlock($title, 'Bold '.self::TITLE_SIZE, self::COLOR_INK, self::TEXT_WIDTH, self::TITLE_LINES);
        $titleHeight = imagesy($titleBlock);
        imagecopy($canvas, $titleBlock, self::PAD_X, self::TITLE_TOP, 0, 0, imagesx($titleBlock), $titleHeight);

        $bodyTop = self::TITLE_TOP + $titleHeight + self::BODY_GAP;
        $bodyHeight = self::BODY_BOTTOM - $bodyTop;
        $bodyBlock = $this->bodyBlock($body, $bodyHeight, $truncated);
        imagecopy($canvas, $bodyBlock, self::PAD_X, $bodyTop, 0, 0, imagesx($bodyBlock), imagesy($bodyBlock));

        $this->drawBrand($canvas);

        return $this->png($canvas);
    }

    /**
     * The card for what has no image of its own: adult content, and copy-pastas whose image is not ready.
     *
     * @throws OgImageRenderFailed
     */
    public function renderGeneric(): string
    {
        $canvas = $this->card();

        $title = $this->textBlock('Copy-pastas', 'Bold 96', self::COLOR_INK, self::TEXT_WIDTH, 1);
        imagecopy($canvas, $title, self::PAD_X, 200, 0, 0, imagesx($title), imagesy($title));

        $tagline = $this->textBlock(__('public.og.tagline'), 'Regular 40', self::COLOR_MUTED, self::TEXT_WIDTH, 2);
        imagecopy($canvas, $tagline, self::PAD_X, 330, 0, 0, imagesx($tagline), imagesy($tagline));

        return $this->png($canvas);
    }

    /**
     * The card with its background, border and brand mark, drawn at 2x and scaled down so the edges are smooth.
     */
    private function card(): GdImage
    {
        $scale = 2;
        $big = imagecreatetruecolor(self::WIDTH * $scale, self::HEIGHT * $scale);

        imagefilledrectangle($big, 0, 0, self::WIDTH * $scale, self::HEIGHT * $scale, $this->color($big, self::COLOR_BG));
        $this->roundedRectangle($big, 40 * $scale, 40 * $scale, (self::WIDTH - 40) * $scale, (self::HEIGHT - 40) * $scale, 28 * $scale, $this->color($big, self::COLOR_BORDER));
        $this->roundedRectangle($big, 41 * $scale, 41 * $scale, (self::WIDTH - 41) * $scale, (self::HEIGHT - 41) * $scale, 27 * $scale, $this->color($big, self::COLOR_SURFACE));

        $this->drawBrandMark($big, self::PAD_X * $scale, self::BRAND_TOP * $scale, 48 * $scale);

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        imagecopyresampled($canvas, $big, 0, 0, 0, 0, self::WIDTH, self::HEIGHT, self::WIDTH * $scale, self::HEIGHT * $scale);

        return $canvas;
    }

    private function drawBrand(GdImage $canvas): void
    {
        $name = $this->textBlock('copy-pastas', 'Bold 30', self::COLOR_INK, 400, 1);

        imagecopy($canvas, $name, self::PAD_X + 48 + 16, self::BRAND_TOP + (int) ((48 - imagesy($name)) / 2), 0, 0, imagesx($name), imagesy($name));
    }

    /**
     * The design's mark: a lime tile with a copy icon.
     */
    private function drawBrandMark(GdImage $canvas, int $x, int $y, int $size): void
    {
        $this->roundedRectangle($canvas, $x, $y, $x + $size, $y + $size, (int) ($size * 0.3), $this->color($canvas, self::COLOR_ACCENT));

        $ink = $this->color($canvas, self::COLOR_BG);
        $unit = $size / 24;
        imagesetthickness($canvas, max(2, (int) round($unit * 2.2)));

        imagerectangle($canvas, (int) ($x + 9 * $unit), (int) ($y + 9 * $unit), (int) ($x + 18 * $unit), (int) ($y + 18 * $unit), $ink);
        imageline($canvas, (int) ($x + 6 * $unit), (int) ($y + 15 * $unit), (int) ($x + 6 * $unit), (int) ($y + 6 * $unit), $ink);
        imageline($canvas, (int) ($x + 6 * $unit), (int) ($y + 6 * $unit), (int) ($x + 15 * $unit), (int) ($y + 6 * $unit), $ink);
        imagesetthickness($canvas, 1);
    }

    /**
     * The first paragraphs of the body one under the other, in a transparent block of the given height. When there is
     * more than fits, the end of the block fades out.
     */
    private function bodyBlock(string $body, int $height, bool $truncated): GdImage
    {
        $block = imagecreatetruecolor(self::TEXT_WIDTH, $height);
        imagealphablending($block, false);
        imagesavealpha($block, true);
        imagefill($block, 0, 0, imagecolorallocatealpha($block, 0, 0, 0, 127));
        imagealphablending($block, true);

        $paragraphs = array_values(array_filter(
            array_map('trim', preg_split('/\R/u', $body) ?: []),
            fn (string $paragraph): bool => $paragraph !== '',
        ));

        $overflow = $truncated || count($paragraphs) > self::MAX_BODY_PARAGRAPHS;
        $y = 0;

        foreach (array_slice($paragraphs, 0, self::MAX_BODY_PARAGRAPHS) as $paragraph) {
            if ($y >= $height) {
                $overflow = true;

                break;
            }

            $text = $this->textBlock($paragraph, 'Regular '.self::BODY_SIZE, self::COLOR_MUTED, self::TEXT_WIDTH, self::MAX_LINES_PER_PARAGRAPH);
            imagecopy($block, $text, 0, $y, 0, 0, imagesx($text), imagesy($text));
            $y += imagesy($text) + self::PARAGRAPH_GAP;
        }

        if ($y > $height) {
            $overflow = true;
        }

        if ($overflow) {
            $this->fadeEnd($block, $height);
        }

        return $block;
    }

    /**
     * Makes the last rows of the block progressively transparent.
     */
    private function fadeEnd(GdImage $block, int $height): void
    {
        $width = imagesx($block);
        $fade = min(self::FADE_HEIGHT, $height);
        imagealphablending($block, false);

        for ($row = $height - $fade; $row < $height; $row++) {
            $factor = ($row - ($height - $fade) + 1) / $fade;

            for ($column = 0; $column < $width; $column++) {
                $pixel = imagecolorat($block, $column, $row);
                $alpha = ($pixel >> 24) & 0x7F;
                $newAlpha = (int) round($alpha + (127 - $alpha) * $factor);

                imagesetpixel($block, $column, $row, ($pixel & 0x00FFFFFF) | ($newAlpha << 24));
            }
        }

        imagealphablending($block, true);
    }

    /**
     * Draws plain text with pango-view and returns it as a transparent image as wide as the block. With a line limit
     * the last line ends in an ellipsis when the text does not fit.
     *
     * @throws OgImageRenderFailed
     */
    private function textBlock(string $text, string $style, string $color, int $width, int $maxLines): GdImage
    {
        $directory = $this->runtimeDirectory();
        $input = tempnam($directory, 'og-in-');
        $output = tempnam($directory, 'og-out-').'.png';

        if ($input === false) {
            throw new OgImageRenderFailed('No se pudo crear el fichero temporal del texto.');
        }

        try {
            file_put_contents($input, $text);

            $process = new Process([
                $this->pangoView,
                '--no-display',
                '--dpi=72',
                '--antialias=gray',
                '--font='.self::FONT_FAMILIES.' '.$style,
                '--width='.$width,
                '--wrap=word-char',
                '--ellipsize=end',
                '--height=-'.$maxLines,
                '--foreground='.$color,
                '--background=transparent',
                '--margin=0',
                '--align=left',
                '--output='.$output,
                $input,
            ], null, ['FONTCONFIG_FILE' => $this->fontConfiguration(), 'HOME' => $directory], null, $this->timeout);

            try {
                $process->run();
            } catch (ProcessTimedOutException $exception) {
                throw new OgImageRenderFailed('pango-view superó el límite de '.$this->timeout.' segundos.', 0, $exception);
            }

            if (! $process->isSuccessful() || ! is_file($output)) {
                throw new OgImageRenderFailed('pango-view falló: '.trim($process->getErrorOutput()));
            }

            return $this->load((string) file_get_contents($output));
        } finally {
            @unlink($input);
            @unlink(substr($output, 0, -4));
            @unlink($output);
        }
    }

    /**
     * @throws OgImageRenderFailed
     */
    private function load(string $png): GdImage
    {
        $size = @getimagesizefromstring($png);

        if ($size === false || $size[0] * $size[1] > self::MAX_BLOCK_PIXELS) {
            throw new OgImageRenderFailed('pango-view devolvió una imagen inválida o demasiado grande.');
        }

        $image = imagecreatefromstring($png);

        if ($image === false) {
            throw new OgImageRenderFailed('No se pudo leer la imagen de pango-view.');
        }

        imagealphablending($image, true);
        imagesavealpha($image, true);

        return $image;
    }

    private function png(GdImage $canvas): string
    {
        ob_start();

        try {
            imagepng($canvas, null, 6);
        } catch (Throwable $exception) {
            ob_end_clean();

            throw new OgImageRenderFailed('No se pudo codificar el PNG.', 0, $exception);
        }

        return (string) ob_get_clean();
    }

    private function roundedRectangle(GdImage $image, int $x1, int $y1, int $x2, int $y2, int $radius, int $color): void
    {
        imagefilledrectangle($image, $x1 + $radius, $y1, $x2 - $radius, $y2, $color);
        imagefilledrectangle($image, $x1, $y1 + $radius, $x2, $y2 - $radius, $color);

        foreach ([[$x1 + $radius, $y1 + $radius], [$x2 - $radius, $y1 + $radius], [$x1 + $radius, $y2 - $radius], [$x2 - $radius, $y2 - $radius]] as [$cx, $cy]) {
            imagefilledellipse($image, $cx, $cy, $radius * 2, $radius * 2, $color);
        }
    }

    private function color(GdImage $image, string $hex): int
    {
        $hex = ltrim($hex, '#');

        return (int) imagecolorallocate($image, (int) hexdec(substr($hex, 0, 2)), (int) hexdec(substr($hex, 2, 2)), (int) hexdec(substr($hex, 4, 2)));
    }

    private function runtimeDirectory(): string
    {
        $directory = $this->runtimeDirectory ?? storage_path('framework/og');

        if (! is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return is_dir($directory) ? $directory : sys_get_temp_dir();
    }

    /**
     * A fontconfig file that adds the application's fonts to the system's, with its own cache directory, so no
     * `fc-cache` step is needed and it works the same in Sail, CI and the production image.
     */
    private function fontConfiguration(): string
    {
        $directory = $this->runtimeDirectory();
        $fonts = $this->fontsDirectory ?? resource_path('fonts/og');
        $path = $directory.'/fonts.conf';

        $contents = <<<XML
            <?xml version="1.0"?>
            <!DOCTYPE fontconfig SYSTEM "fonts.dtd">
            <fontconfig>
                <include ignore_missing="yes">/etc/fonts/fonts.conf</include>
                <dir>{$fonts}</dir>
                <cachedir>{$directory}/fontconfig</cachedir>
            </fontconfig>
            XML;

        if (! is_file($path) || file_get_contents($path) !== $contents) {
            file_put_contents($path, $contents);
        }

        return $path;
    }
}
