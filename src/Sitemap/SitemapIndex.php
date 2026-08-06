<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

use Carbon\Carbon;
use DateTime;

class SitemapIndex
{
    /** @var array<int, array{url: string, lastModificationDate: Carbon}> */
    protected array $tags = [];

    public static function create(): self
    {
        return new self;
    }

    public function add(string $url): static
    {
        $this->tags[] = [
            'url'                  => $url,
            'lastModificationDate' => Carbon::now(),
        ];

        return $this;
    }

    public function render(): string
    {
        $lines = [
            Xml::DECLARATION,
            '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">',
        ];

        foreach ($this->tags as $tag) {
            $lines[] = '    <sitemap>';
            $lines[] = '        <loc>' . Xml::escape($tag['url']) . '</loc>';
            $lines[] = '        <lastmod>' . $tag['lastModificationDate']->format(DateTime::ATOM) . '</lastmod>';
            $lines[] = '    </sitemap>';
        }

        $lines[] = '</sitemapindex>';

        return implode("\n", $lines) . "\n";
    }

    public function writeToFile(string $path): static
    {
        file_put_contents($path, $this->render());

        return $this;
    }
}
