<?php

declare(strict_types=1);

namespace FrankenCms\Sitemap;

use Carbon\Carbon;
use DateTime;
use DateTimeInterface;

class Url
{
    public ?Carbon $lastModificationDate = null;

    public ?string $changeFrequency = null;

    public ?float $priority = null;

    /** @var array<int, array{url: string, caption: string}> */
    public array $images = [];

    public function __construct(
        public string $url
    ) {}

    public static function create(string $url): self
    {
        return new self($url);
    }

    public function setLastModificationDate(DateTimeInterface $lastModificationDate): static
    {
        $this->lastModificationDate = Carbon::instance($lastModificationDate);

        return $this;
    }

    public function setChangeFrequency(string $changeFrequency): static
    {
        $this->changeFrequency = $changeFrequency;

        return $this;
    }

    public function setPriority(float $priority): static
    {
        $this->priority = $priority;

        return $this;
    }

    public function addImage(string $url, string $caption = ''): static
    {
        $this->images[] = ['url' => $url, 'caption' => $caption];

        return $this;
    }

    /**
     * Render this URL as sitemap XML lines
     *
     * @return array<int, string>
     */
    public function toXmlLines(): array
    {
        $lines = ['    <url>'];
        $lines[] = '        <loc>' . Xml::escape($this->url) . '</loc>';

        if ($this->lastModificationDate !== null) {
            $lines[] = '        <lastmod>' . $this->lastModificationDate->format(DateTime::ATOM) . '</lastmod>';
        }

        if ($this->changeFrequency !== null) {
            $lines[] = '        <changefreq>' . Xml::escape($this->changeFrequency) . '</changefreq>';
        }

        if ($this->priority !== null) {
            $lines[] = '        <priority>' . number_format($this->priority, 1) . '</priority>';
        }

        foreach ($this->images as $image) {
            $lines[] = '        <image:image>';
            $lines[] = '            <image:loc>' . Xml::escape($image['url']) . '</image:loc>';

            if ($image['caption'] !== '') {
                $lines[] = '            <image:caption>' . Xml::escape($image['caption']) . '</image:caption>';
            }

            $lines[] = '        </image:image>';
        }

        $lines[] = '    </url>';

        return $lines;
    }
}
