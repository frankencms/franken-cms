<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

class Sitemap
{
    /** @var array<int, Url> */
    protected array $tags = [];

    public static function create(): self
    {
        return new self;
    }

    public function add(Url $url): static
    {
        $this->tags[] = $url;

        return $this;
    }

    /** @return array<int, Url> */
    public function getTags(): array
    {
        return $this->tags;
    }

    public function render(): string
    {
        $lines = [
            Xml::DECLARATION,
            '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">',
        ];

        $seen = [];
        foreach ($this->tags as $tag) {
            if (isset($seen[$tag->url])) {
                continue;
            }
            $seen[$tag->url] = true;

            $lines = array_merge($lines, $tag->toXmlLines());
        }

        $lines[] = '</urlset>';

        return implode("\n", $lines) . "\n";
    }

    public function writeToFile(string $path): static
    {
        file_put_contents($path, $this->render());

        return $this;
    }
}
