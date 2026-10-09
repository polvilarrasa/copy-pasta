<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

/**
 * Arbitrary Tailwind values (bg-[#fff], w-[350px], [&>svg]:size-4) bypass the design tokens. Views may only use
 * them where no token exists. Every exception names the view, the class and the reason; temporary ones are removed
 * when the view is migrated to the design system in Fase 14b.
 *
 * @var array<string, array<string, string>>
 */
const ARBITRARY_VALUE_EXCEPTIONS = [
    'resources/views/components/impersonation-banner.blade.php' => [
        'z-[60]' => 'Above everything else, including the modal overlays at z-50, so leaving impersonation is always reachable.',
    ],
];

/**
 * @return array<int, string> the class tokens inside class attributes and PHP class arrays of one view
 */
function classTokens(string $contents): array
{
    preg_match_all('/(?<![\w-])(?:class|:class)\s*=\s*"([^"]*)"/', $contents, $attributes);
    preg_match_all('/->class\(\s*\[(.*?)\]\s*\)/s', $contents, $arrays);

    $strings = [...$attributes[1], ...$arrays[1]];

    return collect($strings)
        ->flatMap(fn (string $string): array => preg_split('/[\s\'",()]+/', $string, -1, PREG_SPLIT_NO_EMPTY) ?: [])
        ->reject(fn (string $token): bool => str_contains($token, '$'))
        ->filter(fn (string $token): bool => str_contains($token, '[') && str_contains($token, ']'))
        ->values()
        ->all();
}

test('no view uses an arbitrary Tailwind value outside the justified exceptions', function (): void {
    $violations = [];

    foreach (File::allFiles(resource_path('views')) as $file) {
        $relative = 'resources/views/'.str_replace(DIRECTORY_SEPARATOR, '/', $file->getRelativePathname());
        $allowed = ARBITRARY_VALUE_EXCEPTIONS[$relative] ?? [];

        foreach (classTokens($file->getContents()) as $token) {
            if (! array_key_exists($token, $allowed)) {
                $violations[] = "{$relative}: {$token}";
            }
        }
    }

    expect($violations)->toBe([]);
});

test('every arbitrary value exception still matches a real class in its view', function (): void {
    $stale = [];

    foreach (ARBITRARY_VALUE_EXCEPTIONS as $relative => $tokens) {
        $contents = File::get(base_path($relative));

        foreach (array_keys($tokens) as $token) {
            if (! str_contains($contents, $token)) {
                $stale[] = "{$relative}: {$token}";
            }
        }
    }

    expect($stale)->toBe([]);
});
