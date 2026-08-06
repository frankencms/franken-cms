<?php

use FrankenCms\Sitemap\UrlExclusionMatcher;

describe('UrlExclusionMatcher', function () {
    test('matches exact url', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/private']);

        expect($matcher->matches('https://example.com/private'))->toBeTrue();
        expect($matcher->matches('https://example.com/public'))->toBeFalse();
    });

    test('matches wildcard patterns', function () {
        $matcher = new UrlExclusionMatcher(['*/admin-*']);

        expect($matcher->matches('https://example.com/admin-dashboard'))->toBeTrue();
        expect($matcher->matches('https://example.com/about'))->toBeFalse();
    });

    test('does not partially match without wildcards', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/private']);

        expect($matcher->matches('https://example.com/private-eye'))->toBeFalse();
    });

    test('returns false with no patterns', function () {
        $matcher = new UrlExclusionMatcher([]);

        expect($matcher->matches('https://example.com/anything'))->toBeFalse();
    });

    test('escapes regex special characters in patterns', function () {
        $matcher = new UrlExclusionMatcher(['https://example.com/page?tab=1']);

        expect($matcher->matches('https://example.com/page?tab=1'))->toBeTrue();
        expect($matcher->matches('https://example.com/pageXtab=1'))->toBeFalse();
    });
});
