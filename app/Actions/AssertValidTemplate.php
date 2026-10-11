<?php

declare(strict_types=1);

namespace App\Actions;

use App\Support\TemplateVariables;
use Illuminate\Validation\ValidationException;

class AssertValidTemplate
{
    /**
     * A copy-pasta marked as template needs at least one valid variable and at most ten distinct ones. Without the
     * mark, `{{...}}` is plain text and nothing is checked. Publishing and editing both call it, so the rule does not
     * depend on the form.
     */
    public function handle(bool $isTemplate, string $body): void
    {
        if (! $isTemplate) {
            return;
        }

        $count = count(TemplateVariables::extract($body));

        throw_if($count === 0, ValidationException::withMessages(['is_template' => __('app.errors.template_no_variables')]));

        throw_if(
            $count > TemplateVariables::MAX_DISTINCT,
            ValidationException::withMessages(['is_template' => __('app.errors.template_too_many_variables', ['max' => TemplateVariables::MAX_DISTINCT])]),
        );
    }
}
