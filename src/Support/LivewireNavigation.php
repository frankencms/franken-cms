<?php

namespace FrankenCms\Support;

use Illuminate\Support\Str;

class LivewireNavigation
{
    /**
     * The wire:navigate attribute for a link, or '' when the link should
     * get a normal full page load (feature off, new tab, external, file).
     */
    public static function attribute(?string $url, ?string $target = null): string
    {
        if (! config('franken-cms.livewire.navigate', false)) {
            return '';
        }

        if (($target ?? '_self') !== '_self' || ! self::isNavigable($url)) {
            return '';
        }

        return config('franken-cms.livewire.navigate_prefetch', false)
            ? 'wire:navigate.hover'
            : 'wire:navigate';
    }

    /**
     * Whether a URL points at a front-end page on this site that
     * Livewire can fetch and swap in.
     */
    public static function isNavigable(?string $url): bool
    {
        $url = trim((string) $url);

        if ($url === '' || str_starts_with($url, '#')) {
            return false;
        }

        $parts = parse_url($url);

        if ($parts === false) {
            return false;
        }

        $scheme = strtolower($parts['scheme'] ?? '');

        if ($scheme !== '' && ! in_array($scheme, ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['host'])) {
            $appHost = parse_url(url('/'), PHP_URL_HOST);

            if (strcasecmp($parts['host'], (string) $appHost) !== 0) {
                return false;
            }
        }

        $path = trim($parts['path'] ?? '', '/');

        // Files (sitemap.xml, robots.txt, uploads) need a real download
        if (pathinfo($path, PATHINFO_EXTENSION) !== '') {
            return false;
        }

        $except = config('franken-cms.livewire.navigate_except', []);

        return ! ($path !== '' && Str::is($except, $path));
    }
}
