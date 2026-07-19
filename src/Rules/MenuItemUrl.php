<?php

namespace FrankenCms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Translation\PotentiallyTranslatedString;

class MenuItemUrl implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * Accepts absolute http(s) URLs, root-relative paths (/about),
     * anchors (#section), mailto: and tel: links.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || $this->passes($value)) {
            return;
        }

        $fail('The :attribute must be a full URL (https://example.com), a relative path (/about), an anchor (#section), or a mailto:/tel: link.');
    }

    protected function passes(string $value): bool
    {
        if (str_starts_with($value, '/')) {
            return true;
        }

        if (str_starts_with($value, '#')) {
            return strlen($value) > 1;
        }

        if (str_starts_with($value, 'mailto:')) {
            return filter_var(substr($value, 7), FILTER_VALIDATE_EMAIL) !== false;
        }

        if (str_starts_with($value, 'tel:')) {
            return strlen($value) > 4;
        }

        return Validator::make(['url' => $value], ['url' => 'url:http,https'])->passes();
    }
}
