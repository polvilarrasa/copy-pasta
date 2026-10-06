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
    'resources/views/flux/navlist/group.blade.php' => [
        'mb-[2px]' => 'Flux override, removed in 14b with Flux.',
        'dark:hover:bg-white/[7%]' => 'Flux override, removed in 14b with Flux.',
        'space-y-[2px]' => 'Flux override, removed in 14b with Flux.',
        'inset-y-[3px]' => 'Flux override, removed in 14b with Flux.',
    ],
    'resources/views/components/copypasta-card.blade.php' => [
        '[unicode-bidi:isolate]' => 'Replaced by bidi-isolate in the new card; removed in 14b.',
    ],
    'resources/views/public/copypasta.blade.php' => [
        '[unicode-bidi:isolate]' => 'Replaced by bidi-isolate in the new card; removed in 14b.',
    ],
    'resources/views/components/impersonation-banner.blade.php' => [
        'z-[60]' => 'Above the sticky header and the Flux overlays; removed in 14b.',
    ],
    'resources/views/layouts/app/header.blade.php' => [
        '[&>div>svg]:size-5' => 'Flux header layout, removed in 14b.',
    ],
    'resources/views/layouts/auth/split.blade.php' => [
        'sm:w-[350px]' => 'Flux auth layout, removed in 14b.',
    ],
    'resources/views/pages/settings/layout.blade.php' => [
        'md:w-[220px]' => 'Settings sidebar width, removed in 14b.',
    ],
    'resources/views/pages/settings/⚡two-factor-setup-modal.blade.php' => [
        '[&>div]:flex-1' => 'Flux modal content, removed in 14b.',
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
