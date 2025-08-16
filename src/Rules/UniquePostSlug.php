<?php

namespace FrankenCms\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniquePostSlug implements ValidationRule
{
    protected ?int $ignorePostId;

    public function __construct(?int $ignorePostId = null)
    {
        $this->ignorePostId = $ignorePostId;
    }

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $exists = DB::table('posts')
            ->where('slug', $value)
            ->when($this->ignorePostId, fn ($query) => $query->where('id', '!=', $this->ignorePostId))
            ->exists();

        if ($exists) {
            $fail("The {$attribute} must be unique. A post with this slug already exists.");
        }
    }
}
