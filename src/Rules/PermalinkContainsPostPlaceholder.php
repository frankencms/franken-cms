<?php

namespace FrankenCms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PermalinkContainsPostPlaceholder implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        foreach ($value as $item) {
            if (is_string($item) && (str_contains($item, '%postname%') || str_contains($item, '%post_id%'))) {
                return;
            }
        }

        $fail('The custom permalink structure must contain at least one value with %postname% or %post_id% in order to resolve the url.');
    }
}
