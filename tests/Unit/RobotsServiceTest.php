<?php

use FrankenCms\Services\RobotsService;
use FrankenCms\Services\SitemapService;
use FrankenCms\Settings\RobotsSettings;
use FrankenCms\Settings\SitemapSettings;
use Illuminate\Support\Facades\File;
use Spatie\LaravelSettings\Settings;

/**
 * Create a mock RobotsSettings with proper initialization.
 * Spatie Settings has typed internal properties that require special handling.
 */
function createRobotsSettingsMock(): RobotsSettings
{
    $settings = Mockery::mock(RobotsSettings::class)->makePartial();

    // Set the 'loaded' property to true to prevent the load() method from being called
    // The property is in the parent Spatie\LaravelSettings\Settings class
    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    // Set default property values
    $settings->enabled = true;
    $settings->discourage_indexing = false;
    $settings->user_agents = [
        [
            'user_agent'  => '*',
            'rules'       => ['Allow: /'],
            'crawl_delay' => null,
        ],
    ];

    return $settings;
}

/**
 * Create a mock SitemapSettings with proper initialization.
 */
function createRobotsSitemapSettingsMock(): SitemapSettings
{
    $settings = Mockery::mock(SitemapSettings::class)->makePartial();

    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    $settings->enabled = true;
    $settings->custom_sitemaps = [];

    return $settings;
}

beforeEach(function () {
    // Clean up any test robots.txt file
    $robotsPath = public_path('robots.txt');
    if (File::exists($robotsPath)) {
        File::delete($robotsPath);
    }

    // Create properly initialized mock settings
    $this->settings = createRobotsSettingsMock();
    $this->sitemapSettings = createRobotsSitemapSettingsMock();
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
        $content = $service->generate();

        expect($content)->not->toContain('Sitemap:');
    });

    test('includes custom sitemaps from sitemap settings', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(false);

        $this->sitemapSettings->custom_sitemaps = ['/custom-sitemap.xml'];

        $service = new RobotsService($this->settings, $sitemapService, $this->sitemapSettings);
        $content = $service->generate();

        expect($content)->toContain('Sitemap: ');
        expect($content)->toContain('custom-sitemap.xml');
    });

    test('converts relative sitemap URLs to absolute', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(false);

        $this->sitemapSettings->custom_sitemaps = ['/custom-sitemap.xml'];

        $service = new RobotsService($this->settings, $sitemapService, $this->sitemapSettings);
        $content = $service->generate();

        expect($content)->toContain('Sitemap: http'); // Should be absolute URL
    });

    test('keeps absolute sitemap URLs unchanged', function () {
        $sitemapService = Mockery::mock(SitemapService::class);
        $sitemapService->shouldReceive('isEnabled')->andReturn(false);

        $this->sitemapSettings->custom_sitemaps = ['https://cdn.example.com/sitemap.xml'];

        $service = new RobotsService($this->settings, $sitemapService, $this->sitemapSettings);
        $content = $service->generate();

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

describe('Discourage indexing', function () {
    test('blocks all search engines when discourage_indexing is enabled', function () {
        $this->settings->discourage_indexing = true;

        $content = $this->service->generate();

        expect($content)->toContain('User-agent: *');
        expect($content)->toContain('Disallow: /');
        expect($content)->toContain('discourage indexing');
    });

    test('uses normal rules when discourage_indexing is disabled', function () {
        $this->settings->discourage_indexing = false;
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Allow: /');
        expect($content)->not->toContain('discourage indexing');
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

        // Add the same sitemap URL manually via custom_sitemaps
        $this->sitemapSettings->custom_sitemaps = [url('/sitemap.xml')];

        $service = new RobotsService($this->settings, $sitemapService, $this->sitemapSettings);
        $content = $service->generate();

        // Count occurrences of "Sitemap: "
        $count = substr_count($content, 'Sitemap: ');
        expect($count)->toBe(1); // Should only appear once despite being added twice
    });
});

describe('Cache management', function () {
    test('clearCache clears the robots.txt cache', function () {
        // Generate content to populate cache
        $this->service->getContent();

        // Verify cache exists
        expect(Cache::has('robots_txt'))->toBeTrue();

        // Clear cache
        $this->service->clearCache();

        // Verify cache is cleared
        expect(Cache::has('robots_txt'))->toBeFalse();
    });

    test('caches dynamic content', function () {
        // First call should cache
        $firstResult = $this->service->getContent();

        // Modify settings - should NOT affect cached result
        $this->settings->user_agents = [
            [
                'user_agent'  => 'Googlebot',
                'rules'       => ['Disallow: /secret'],
                'crawl_delay' => null,
            ],
        ];

        // Second call should return cached result
        $secondResult = $this->service->getContent();

        expect($firstResult)->toBe($secondResult);
        expect($secondResult)->not->toContain('Googlebot');
    });

    test('clearCache allows fresh generation', function () {
        // First call caches result
        $this->service->getContent();

        // Modify settings
        $this->settings->user_agents = [
            [
                'user_agent'  => 'ModifiedBot',
                'rules'       => ['Disallow: /new-rule'],
                'crawl_delay' => null,
            ],
        ];

        // Clear cache
        $this->service->clearCache();

        // Now should use new settings
        $freshResult = $this->service->getContent();

        expect($freshResult)->toContain('ModifiedBot');
        expect($freshResult)->toContain('Disallow: /new-rule');
    });
});

describe('Header generation', function () {
    test('includes domain in header comment', function () {
        config()->set('app.url', 'https://example.com');

        $content = $this->service->generate();

        expect($content)->toContain('# robots.txt for example.com');
    });

    test('includes last updated date', function () {
        $content = $this->service->generate();

        expect($content)->toContain('# Last updated: ' . now()->toDateString());
    });

    test('includes auto-generated notice', function () {
        $content = $this->service->generate();

        expect($content)->toContain('# Auto-generated by FrankenCMS');
    });
});

describe('Crawl delay edge cases', function () {
    test('ignores negative crawl delay', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: /'],
                'crawl_delay' => -5,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->not->toContain('Crawl-delay');
    });

    test('includes positive crawl delay', function () {
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
});

describe('User agent edge cases', function () {
    test('handles empty user_agents array', function () {
        $this->settings->user_agents = [];

        $content = $this->service->generate();

        // Should still generate valid output (just headers, no user-agent blocks)
        expect($content)->toContain('# Auto-generated by FrankenCMS');
        expect($content)->not->toContain('User-agent:');
    });

    test('handles missing user_agent key in config', function () {
        $this->settings->user_agents = [
            [
                // No 'user_agent' key - should default to '*'
                'rules'       => ['Allow: /'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('User-agent: *');
        expect($content)->toContain('Allow: /');
    });

    test('handles missing rules key in config', function () {
        $this->settings->user_agents = [
            [
                'user_agent' => 'TestBot',
                // No 'rules' key - should default to empty array
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('User-agent: TestBot');
        // No Allow/Disallow rules should be present
        expect($content)->not->toContain('Allow:');
        expect($content)->not->toContain('Disallow:');
    });
});

describe('Rule parsing edge cases', function () {
    test('rejects invalid directive types', function () {
        $this->settings->user_agents = [
            [
                'user_agent' => '*',
                'rules'      => [
                    'Allow: /valid',
                    'Sitemap: /invalid-here',  // Sitemap is not Allow/Disallow
                    'Crawl-delay: 5',          // Crawl-delay should be in config, not rules
                    'Host: example.com',       // Host is not valid directive
                ],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Allow: /valid');
        expect($content)->not->toContain('Sitemap: /invalid-here');
        // Note: Crawl-delay in rules will be rejected (not in allowed directives)
        // The actual Crawl-delay comes from crawl_delay config key
    });

    test('handles whitespace in rules', function () {
        $this->settings->user_agents = [
            [
                'user_agent' => '*',
                'rules'      => [
                    '  Allow  :   /with-spaces  ',
                    'Disallow:    /also-spaces   ',
                ],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        expect($content)->toContain('Allow: /with-spaces');
        expect($content)->toContain('Disallow: /also-spaces');
    });

    test('handles empty path in rules', function () {
        $this->settings->user_agents = [
            [
                'user_agent'  => '*',
                'rules'       => ['Allow: ', 'Disallow:'],
                'crawl_delay' => null,
            ],
        ];

        $content = $this->service->generate();

        // Empty paths should still be included (valid in robots.txt)
        expect($content)->toContain('Allow:');
        expect($content)->toContain('Disallow:');
    });
});
