<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

class UrlExclusionMatcher
{
    /**
     * @param  array<int, string>  $patterns  Wildcard patterns matched against full URLs
     */
    public function __construct(
        protected array $patterns
    ) {}

    public function matches(string $url): bool
    {
        foreach ($this->patterns as $pattern) {
            // Support wildcards - escape everything except *, then replace * with .*
            $regex = str_replace('\*', '.*', preg_quote($pattern, '/'));

            if (preg_match('/^' . $regex . '$/', $url)) {
                return true;
            }
        }

        return false;
    }
}
