<?php

declare(strict_types=1);

namespace App\Support;

/**
 * The `{{name}}` variables of a template. A name is letters (accents included), digits and underscores, up to 30
 * characters, with optional spaces inside the braces. Names that differ only in case are one variable, labelled as in
 * its first appearance. Anything else between double braces is plain text. The browser applies the same rule when it
 * fills the template (resources/js/app.js), so keep both in step.
 */
final class TemplateVariables
{
    public const MAX_NAME_LENGTH = 30;

    public const MAX_DISTINCT = 10;

    public const MAX_VALUE_LENGTH = 200;

    private const PATTERN = '/\{\{[ \t]*([\p{L}\p{N}_]{1,30})[ \t]*\}\}/u';

    /**
     * The distinct variables in order of first appearance, as they were first written.
     *
     * @return list<string>
     */
    public static function extract(string $body): array
    {
        preg_match_all(self::PATTERN, $body, $matches);

        $variables = [];

        foreach ($matches[1] as $name) {
            $variables[mb_strtolower($name)] ??= $name;
        }

        return array_values($variables);
    }
}
