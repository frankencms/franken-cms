<?php

use Carbon\Carbon;
use FrankenCms\Sitemap\Sitemap;
use FrankenCms\Sitemap\SitemapIndex;
use FrankenCms\Sitemap\Url;
use Illuminate\Support\Facades\File;

afterEach(function () {
    $path = public_path('sitemap-xml-test.xml');
    if (File::exists($path)) {
        File::delete($path);
    }
});

describe('Url tag', function () {
    test('renders loc only when no optional properties are set', function () {
        $xml = Sitemap::create()
            ->add(Url::create('https://example.com/hello'))
            ->render();

        expect($xml)->toContain('<loc>https://example.com/hello</loc>');
        expect($xml)->not->toContain('<lastmod>');
        expect($xml)->not->toContain('<changefreq>');
        expect($xml)->not->toContain('<priority>');
    });

    test('renders last modification date in W3C/ATOM format', function () {
        $url = Url::create('https://example.com/hello')
            ->setLastModificationDate(Carbon::parse('2026-01-15 10:30:00', 'UTC'));

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<lastmod>2026-01-15T10:30:00+00:00</lastmod>');
    });

    test('renders change frequency', function () {
        $url = Url::create('https://example.com/hello')
            ->setChangeFrequency('daily');

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<changefreq>daily</changefreq>');
    });

    test('renders priority with one decimal place', function () {
        $url = Url::create('https://example.com/hello')
            ->setPriority(0.8);

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<priority>0.8</priority>');
    });

    test('renders whole number priority with one decimal place', function () {
        $url = Url::create('https://example.com/hello')
            ->setPriority(1.0);

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<priority>1.0</priority>');
    });

    test('renders image tags with loc and caption', function () {
        $url = Url::create('https://example.com/hello')
            ->addImage('https://example.com/img.jpg', 'My caption');

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<image:image>');
        expect($xml)->toContain('<image:loc>https://example.com/img.jpg</image:loc>');
        expect($xml)->toContain('<image:caption>My caption</image:caption>');
        expect($xml)->toContain('</image:image>');
    });

    test('omits image caption when empty', function () {
        $url = Url::create('https://example.com/hello')
            ->addImage('https://example.com/img.jpg');

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<image:loc>https://example.com/img.jpg</image:loc>');
        expect($xml)->not->toContain('<image:caption>');
    });

    test('escapes XML special characters in urls and captions', function () {
        $url = Url::create('https://example.com/search?q=a&page=2')
            ->addImage('https://example.com/img.jpg?w=100&h=50', 'Tom & Jerry <heroes>');

        $xml = Sitemap::create()->add($url)->render();

        expect($xml)->toContain('<loc>https://example.com/search?q=a&amp;page=2</loc>');
        expect($xml)->toContain('<image:loc>https://example.com/img.jpg?w=100&amp;h=50</image:loc>');
        expect($xml)->toContain('<image:caption>Tom &amp; Jerry &lt;heroes&gt;</image:caption>');
    });
});

describe('Sitemap rendering', function () {
    test('renders XML declaration and urlset with sitemap namespaces', function () {
        $xml = Sitemap::create()->render();

        expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>');
        expect($xml)->toContain('<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">');
        expect($xml)->toContain('</urlset>');
    });

    test('renders one url element per added url', function () {
        $sitemap = Sitemap::create()
            ->add(Url::create('https://example.com/one'))
            ->add(Url::create('https://example.com/two'))
            ->add(Url::create('https://example.com/three'));

        expect(substr_count($sitemap->render(), '<url>'))->toBe(3);
    });

    test('deduplicates urls with the same location', function () {
        $sitemap = Sitemap::create()
            ->add(Url::create('https://example.com/same'))
            ->add(Url::create('https://example.com/same'));

        expect(substr_count($sitemap->render(), '<url>'))->toBe(1);
    });

    test('writes rendered sitemap to file', function () {
        $path = public_path('sitemap-xml-test.xml');

        $sitemap = Sitemap::create()->add(Url::create('https://example.com/hello'));
        $result = $sitemap->writeToFile($path);

        expect($result)->toBe($sitemap);
        expect(File::get($path))->toBe($sitemap->render());
    });

    test('survives serialization round trip', function () {
        $sitemap = Sitemap::create()->add(
            Url::create('https://example.com/hello')
                ->setLastModificationDate(Carbon::parse('2026-01-15 10:30:00', 'UTC'))
                ->setChangeFrequency('weekly')
                ->setPriority(0.5)
                ->addImage('https://example.com/img.jpg', 'Caption')
        );

        $restored = unserialize(serialize($sitemap));

        expect($restored->render())->toBe($sitemap->render());
    });
});

describe('SitemapIndex rendering', function () {
    test('renders XML declaration and sitemapindex element', function () {
        $xml = SitemapIndex::create()->render();

        expect($xml)->toStartWith('<?xml version="1.0" encoding="UTF-8"?>');
        expect($xml)->toContain('<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">');
        expect($xml)->toContain('</sitemapindex>');
    });

    test('renders sitemap entries with loc and lastmod', function () {
        $xml = SitemapIndex::create()
            ->add('https://example.com/sitemap-pages.xml')
            ->add('https://example.com/sitemap-posts.xml')
            ->render();

        expect($xml)->toContain('<sitemap>');
        expect($xml)->toContain('<loc>https://example.com/sitemap-pages.xml</loc>');
        expect($xml)->toContain('<loc>https://example.com/sitemap-posts.xml</loc>');
        expect($xml)->toContain('<lastmod>');
        expect(substr_count($xml, '<sitemap>'))->toBe(2);
    });

    test('escapes XML special characters in sitemap locations', function () {
        $xml = SitemapIndex::create()
            ->add('https://example.com/sitemap.xml?type=news&lang=en')
            ->render();

        expect($xml)->toContain('<loc>https://example.com/sitemap.xml?type=news&amp;lang=en</loc>');
    });

    test('writes rendered index to file', function () {
        $path = public_path('sitemap-xml-test.xml');

        $index = SitemapIndex::create()->add('https://example.com/sitemap-pages.xml');
        $result = $index->writeToFile($path);

        expect($result)->toBe($index);
        expect(File::get($path))->toBe($index->render());
    });

    test('survives serialization round trip', function () {
        $index = SitemapIndex::create()->add('https://example.com/sitemap-pages.xml');

        $restored = unserialize(serialize($index));

        expect($restored->render())->toBe($index->render());
    });
});
