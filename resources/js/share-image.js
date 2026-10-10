/**
 * "Compartir como imagen": the card of a copy-pasta drawn on a canvas, with no library. It follows the same template
 * as the Open Graph image the server draws (author, title, first lines, brand mark and name), in the three styles of
 * the design, on a square of 1080 px for stories and chats.
 */

export const SIZE = 1080;

export const SHARE_IMAGE_STYLES = {
    midnight: { bg: '#0E0D1A', ink: '#F1F0FF', dim: '#A5A2D0', markBg: '#C6F432', markInk: '#0E0D1A', swatch: '#C6F432' },
    light: { bg: '#F3F2FF', ink: '#15132B', dim: '#55527C', markBg: '#5B2EFF', markInk: '#FFFFFF', swatch: '#5B2EFF' },
    lime: { bg: '#C6F432', ink: '#0E0D1A', dim: '#2F3A06', markBg: '#0E0D1A', markInk: '#C6F432', swatch: '#0E0D1A' },
};

const FONT_FAMILY = "'Bricolage Grotesque Variable', 'Bricolage Grotesque', ui-sans-serif, system-ui, sans-serif";
const PADDING = 80;
const MAX_TITLE_LINES = 4;
const MAX_EXCERPT_LINES = 6;
const EXCERPT_PARAGRAPHS = 3;

// Hebrew, Arabic and their presentation forms: the first letter of a line decides its direction.
const RTL_LETTER = /[֐-ࣿיִ-﷿ﹰ-﻿]/u;
const ANY_LETTER = /\p{L}/u;

const INVISIBLE = /[‪-‮⁦-⁩​‌﻿]/gu;

const graphemes = (text) => (typeof Intl.Segmenter === 'function'
    ? Array.from(new Intl.Segmenter(undefined, { granularity: 'grapheme' }).segment(text), (part) => part.segment)
    : Array.from(text));

const isRtl = (text) => {
    const first = Array.from(text).find((character) => ANY_LETTER.test(character));

    return first !== undefined && RTL_LETTER.test(first);
};

/**
 * Breaks a paragraph into lines no wider than maxWidth. Words are never split unless one alone is wider than a line,
 * and then it is split between graphemes, so an emoji sequence is never cut in half.
 */
function wrap(context, text, maxWidth) {
    const lines = [];
    let current = '';

    for (const word of text.split(/\s+/u).filter(Boolean)) {
        const attempt = current === '' ? word : `${current} ${word}`;

        if (context.measureText(attempt).width <= maxWidth) {
            current = attempt;

            continue;
        }

        if (current !== '') {
            lines.push(current);
            current = '';
        }

        if (context.measureText(word).width <= maxWidth) {
            current = word;

            continue;
        }

        for (const piece of graphemes(word)) {
            if (current !== '' && context.measureText(current + piece).width > maxWidth) {
                lines.push(current);
                current = '';
            }

            current += piece;
        }
    }

    if (current !== '') {
        lines.push(current);
    }

    return lines;
}

function roundedRectangle(context, x, y, width, height, radius) {
    context.beginPath();
    context.moveTo(x + radius, y);
    context.arcTo(x + width, y, x + width, y + height, radius);
    context.arcTo(x + width, y + height, x, y + height, radius);
    context.arcTo(x, y + height, x, y, radius);
    context.arcTo(x, y, x + width, y, radius);
    context.closePath();
}

function drawLines(context, lines, x, y, lineHeight, maxWidth, color) {
    context.fillStyle = color;

    lines.forEach((line, index) => {
        const rtl = isRtl(line);

        context.direction = rtl ? 'rtl' : 'ltr';
        context.textAlign = rtl ? 'right' : 'left';
        context.fillText(line, rtl ? x + maxWidth : x, y + index * lineHeight);
    });

    context.direction = 'ltr';
    context.textAlign = 'left';
}

/**
 * The design's mark: a rounded tile with a copy icon, 56 px.
 */
function drawMark(context, x, y, size, style) {
    context.fillStyle = style.markBg;
    roundedRectangle(context, x, y, size, size, size * 0.3);
    context.fill();

    const unit = size / 24;

    context.strokeStyle = style.markInk;
    context.lineWidth = unit * 2.4;
    context.lineCap = 'round';
    context.lineJoin = 'round';

    roundedRectangle(context, x + 9 * unit, y + 9 * unit, 9 * unit, 9 * unit, 2 * unit);
    context.stroke();

    context.beginPath();
    context.moveTo(x + 6 * unit, y + 15 * unit);
    context.lineTo(x + 6 * unit, y + 7.5 * unit);
    context.arcTo(x + 6 * unit, y + 6 * unit, x + 7.5 * unit, y + 6 * unit, 1.5 * unit);
    context.lineTo(x + 15 * unit, y + 6 * unit);
    context.stroke();
}

export async function loadShareImageFonts() {
    if (! document.fonts) {
        return;
    }

    await Promise.all([
        document.fonts.load(`800 76px ${FONT_FAMILY}`),
        document.fonts.load(`400 40px ${FONT_FAMILY}`),
    ]).catch(() => {});
}

/**
 * Draws the card on the canvas. The title takes up to four lines and shrinks when it needs more; the excerpt is the
 * first three paragraphs of the body, up to six lines, with an ellipsis when there is more.
 */
export function drawShareImage(canvas, { title, body, author, style: styleKey, brand }) {
    const style = SHARE_IMAGE_STYLES[styleKey] ?? SHARE_IMAGE_STYLES.midnight;
    const context = canvas.getContext('2d');
    const maxWidth = SIZE - PADDING * 2;

    canvas.width = SIZE;
    canvas.height = SIZE;

    context.fillStyle = style.bg;
    context.fillRect(0, 0, SIZE, SIZE);
    context.textBaseline = 'alphabetic';

    const cleanTitle = title.replace(INVISIBLE, '').replace(/\s+/gu, ' ').trim();

    context.font = `700 28px ${FONT_FAMILY}`;
    context.fillStyle = style.dim;
    context.letterSpacing = '2px';
    context.fillText(author.toUpperCase(), PADDING, PADDING + 24);
    context.letterSpacing = '0px';

    let titleSize = 80;
    let titleLines;

    do {
        context.font = `800 ${titleSize}px ${FONT_FAMILY}`;
        titleLines = wrap(context, cleanTitle, maxWidth);
        titleSize -= 6;
    } while (titleLines.length > MAX_TITLE_LINES && titleSize >= 44);

    titleSize += 6;
    titleLines = titleLines.slice(0, MAX_TITLE_LINES);

    const titleLineHeight = Math.round(titleSize * 1.1);
    const titleTop = PADDING + 24 + 40 + titleSize;

    drawLines(context, titleLines, PADDING, titleTop, titleLineHeight, maxWidth, style.ink);

    context.font = `400 40px ${FONT_FAMILY}`;

    const paragraphs = body.replace(INVISIBLE, '').split(/\r?\n/u).map((line) => line.trim()).filter(Boolean);
    const excerptSource = paragraphs.slice(0, EXCERPT_PARAGRAPHS);
    let excerptLines = excerptSource.flatMap((paragraph) => wrap(context, paragraph, maxWidth));
    const truncated = excerptLines.length > MAX_EXCERPT_LINES || paragraphs.length > EXCERPT_PARAGRAPHS;

    excerptLines = excerptLines.slice(0, MAX_EXCERPT_LINES);

    const brandTop = SIZE - PADDING - 56;
    const excerptLineHeight = 60;
    const excerptTop = titleTop + titleLineHeight * (titleLines.length - 1) + 40 + excerptLineHeight;
    const room = Math.max(1, Math.floor((brandTop - 40 - excerptTop) / excerptLineHeight) + 1);

    if (excerptLines.length > room || (truncated && excerptLines.length > 0)) {
        excerptLines = excerptLines.slice(0, Math.min(room, excerptLines.length));
        excerptLines[excerptLines.length - 1] = `${excerptLines[excerptLines.length - 1].replace(/[\s.,;:]+$/u, '')}…`;
    }

    drawLines(context, excerptLines, PADDING, excerptTop, excerptLineHeight, maxWidth, style.dim);

    drawMark(context, PADDING, brandTop, 56, style);
    context.font = `800 40px ${FONT_FAMILY}`;
    context.fillStyle = style.ink;
    context.fillText(brand, PADDING + 56 + 18, brandTop + 40);
}

export function canvasToBlob(canvas) {
    return new Promise((resolve, reject) => {
        canvas.toBlob((blob) => (blob ? resolve(blob) : reject(new Error('empty canvas'))), 'image/png');
    });
}
