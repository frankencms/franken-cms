<?php

use FrankenCms\Services\RobotsService;
use FrankenCms\Services\SitemapService;
use FrankenCms\Settings\RobotsSettings;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Clean up any test robots.txt file
    $robotsPath = public_path('robots.txt');
    if (File::exists($robotsPath)) {
        File::delete($robotsPath);
    }

    // Mock settings with defaults
    $this->settings = Mockery::mock(RobotsSettings::class);
    $this->settings->enabled = true;
    $this->settings->user_agents = [
        [
            'user_agent'  => '*',
            'rules'       => ['Allow: /'],
            'crawl_delay' => null,
        ],
    ];
    $this->settings->additional_sitemaps = [];
    $this->settings->host = null;

    $this->service = new RobotsService($this->settings);
});

afterEach(function () {
    // Clean up test files
    $robotsPath = public_path('robots.txt');
    if (File::exists($robotsPath)) {
        File::delete($robotsPath);
    }
});

describe('Static file detection', function () {
    test('detects when static robots.txt file does not exist', function () {
        expect($this->service->hasStaticFile())->toBeFalse();
    });

    test('detects when static robots.txt file exists', function () {
        File::put(public_path('robots.txt'), 'User-agent: *');

        expect($this->service->hasStaticFile())->toBeTrue();
    });

    test('returns null when getting static content for non-existent file', function () {
        expect($this->service->getStaticContent())->toBeNull();
    });

    test('returns content when static file exists', function () {
        $content = 'User-agent: *' . PHP_EOL . 'Disallow: /admin';
        File::put(public_path('robots.txt'), $content);

        expect($this->service->getStaticContent())->toBe($content);
    });
});

describe('Dynamic robots.txt generation', function () {
    test('returns empty string when disabled', function () {
        $this->settings->enabled = false;

        $content = $this->service->generate();

        expect($content)->toBe('');
    });

    test('generates basic robots.txt with wildcard user agent', function () {
        $content = $this->service->generate();

        expect($content)->toContain('User-agent: *');
        expect($content)->toContain('Allow: /');
    });

    test('generates robots.txt with disallow rules', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Disallow: /admin', 'Disallow: /private'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('User-agent: *');
        expect($content)->toContain('Disallow: /admin');
        expect($content)->toContain('Disallow: /private');
    });

    test('generates robots.txt with allow rules', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /public', 'Allow: /assets'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Allow: /public');
        expect($content)->toContain('Allow: /assets');
    });

    test('generates robots.txt with mixed allow and disallow rules', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Disallow: /admin', 'Allow: /admin/public'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Disallow: /admin');
        expect($content)->toContain('Allow: /admin/public');
    });

    test('generates robots.txt with crawl delay', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => 10,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Crawl-delay: 10');
    });

    test('ignores zero or negative crawl delay', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => 0,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->not->toContain('Crawl-delay');
    });

    test('generates robots.txt with multiple user agents', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => 'Googlebot',
                'rules'       => ['Allow: /'],
                'crawl_delay' => null,
            ],
            [
                'user_agent'  => 'Bingbot',
                'rules'       => ['Allow: /', 'Disallow: /private'],
                'crawl_delay' => 5,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('User-agent: Googlebot');
        expect($content)->toContain('User-agent: Bingbot');
        expect($content)->toContain('Crawl-delay: 5');
    });

    test('generates robots.txt with host directive', function () {
        $this->settings->host = 'https://example.com';

        $content = $this->service->generate();

        expect($content)->toContain('Host: https://example.com');
    });

    test('generates robots.txt with additional sitemaps', function () {
        $this->settings->additional_sitemaps = ['/custom-sitemap.xml'];

        $content = $this->service->generate();

        expect($content)->toContain('Sitemap: ');
        expect($content)->toContain('custom-sitemap.xml');
    });

    test('includes auto-generated sitemap when sitemap service is enabled', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(true);

        $service = new RobotsService($this->settings, $sitemapService);
        $content = $service->generate();

        expect($content)->toContain('Sitemap: ');
        expect($content)->toContain('/sitemap.xml');
    });

    test('does not include auto-generated sitemap when sitemap service is disabled', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(false);

        $service = new RobotsService($this->settings, $sitemapService);

        // Reset settings to have no additional sitemaps
        $this->settings->additional_sitemaps = [];

        $content = $service->generate();

        expect($content)->not->toContain('Sitemap:');
    });

    test('converts relative sitemap URLs to absolute', function () {
        $this->settings->additional_sitemaps = ['/custom-sitemap.xml'];

        $content = $this->service->generate();

        expect($content)->toContain('Sitemap: http'); // Should be absolute URL
    });

    test('keeps absolute sitemap URLs unchanged', function () {
        $this->settings->additional_sitemaps = ['https://cdn.example.com/sitemap.xml'];

        $content = $this->service->generate();

        expect($content)->toContain('Sitemap: https://cdn.example.com/sitemap.xml');
    });

    test('ignores invalid rules without colon', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /', 'InvalidRule'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        // Should generate without errors and include valid rule
        expect($content)->toContain('Allow: /');
        expect($content)->not->toContain('InvalidRule');
    });

    test('handles case-insensitive rule directives', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['DISALLOW: /admin', 'allow: /public'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Disallow: /admin');
        expect($content)->toContain('Allow: /public');
    });
});

describe('Hybrid static/dynamic content', function () {
    test('returns static content when file exists', function () {
        $staticContent = 'User-agent: *' . PHP_EOL . 'Disallow: /admin';
        File::put(public_path('robots.txt'), $staticContent);

        $content = $this->service->getContent();

        expect($content)->toBe($staticContent);
    });

    test('returns dynamic content when no static file exists', function () {
        $content = $this->service->getContent();

        expect($content)->toContain('User-agent: *');
        expect($content)->toContain('Allow: /');
    });

    test('static file takes precedence over dynamic generation', function () {
        $staticContent = 'User-agent: *' . PHP_EOL . 'Disallow: /';
        File::put(public_path('robots.txt'), $staticContent);

        // Even though settings allow everything, static file should be used
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->getContent();

        expect($content)->toBe($staticContent);
        expect($content)->not->toContain('Allow: /');
    });
});

describe('Sitemap URL generation', function () {
    test('removes duplicate sitemap URLs', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(true);

        // Add the same sitemap URL manually
        $this->settings->additional_sitemaps = [url('/sitemap.xml')];

        $service = new RobotsService($this->settings, $sitemapService);
        $content = $service->generate();

        // Count occurrences of "Sitemap: "
        $count = substr_count($content, 'Sitemap: ');
        expect($count)->toBe(1); // Should only appear once despite being added twice
    });
});
