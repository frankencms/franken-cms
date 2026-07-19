<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Services\SitemapService;
use FrankenCms\Settings\SitemapSettings;
use FrankenCms\Tests\Support\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Spatie\LaravelSettings\Settings;
use Spatie\Sitemap\Sitemap;

/**
 * Create a mock SitemapSettings with proper initialization.
 * Spatie Settings has typed internal properties that require special handling.
 */
function createSettingsMock(): SitemapSettings
{
    $settings = Mockery::mock(SitemapSettings::class)->makePartial();

    // Set the 'loaded' property to true to prevent the load() method from being called
    // The property is in the parent Spatie\LaravelSettings\Settings class
    $reflection = new ReflectionProperty(Settings::class, 'loaded');
    $reflection->setAccessible(true);
    $reflection->setValue($settings, true);

    // Set default property values
    $settings->enabled = true;
    $settings->default_change_frequency = 'weekly';
    $settings->default_priority = 0.5;
    $settings->max_urls_per_sitemap = 50000;
    $settings->excluded_paths = [];
    $settings->include_images = true;
    $settings->custom_sitemaps = [];

    return $settings;
}

beforeEach(function () {
    // Create a test user with ID 1 for post author relationship
    User::create([
        'id'       => 1,
        'name'     => 'Test User',
        'email'    => 'test@example.com',
        'password' => bcrypt('password'),
    ]);

    // Clean up any test sitemap files
    $files = glob(public_path('sitemap*.xml'));
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    // Configure reading settings for URL generation
    config()->set('franken-cms.reading_settings', [
        'post_page' => 'blog',
    ]);

    // Clear sitemap cache
    Cache::forget('sitemap_index');
    Cache::forget('sitemap_post');
    Cache::forget('sitemap_page');

    // Create properly initialized mock settings
    $this->settings = createSettingsMock();
    $this->service = new SitemapService($this->settings);
});

afterEach(function () {
    // Clean up test files and database
    $files = glob(public_path('sitemap*.xml'));
    foreach ($files as $file) {
        if (File::exists($file)) {
            File::delete($file);
        }
    }

    Post::query()->delete();

    // Clear sitemap cache
    Cache::forget('sitemap_index');
    Cache::forget('sitemap_post');
    Cache::forget('sitemap_page');
});

describe('Enabled state', function () {
    test('returns true when sitemap is enabled', function () {
        $this->settings->enabled = true;

        expect($this->service->isEnabled())->toBeTrue();
    });

    test('returns false when sitemap is disabled', function () {
        $this->settings->enabled = false;

        expect($this->service->isEnabled())->toBeFalse();
    });

    test('render returns empty string when disabled', function () {
        $this->settings->enabled = false;

        expect($this->service->render())->toBe('');
    });

    test('writeToFile does nothing when disabled', function () {
        $this->settings->enabled = false;

        $this->service->writeToFile();

        expect(File::exists(public_path('sitemap.xml')))->toBeFalse();
    });
});

describe('Sitemap index generation', function () {
    test('generates valid XML sitemap index', function () {
        $sitemap = $this->service->render();

        expect($sitemap)->toContain('<?xml');
        expect($sitemap)->toContain('<sitemapindex');
        expect($sitemap)->toContain('sitemap-pages.xml');
        expect($sitemap)->toContain('sitemap-posts.xml');
    });

    test('includes custom sitemaps in index', function () {
        $this->settings->custom_sitemaps = ['/news-sitemap.xml', 'https://cdn.example.com/products.xml'];

        $sitemap = $this->service->render();

        expect($sitemap)->toContain('news-sitemap.xml');
        expect($sitemap)->toContain('https://cdn.example.com/products.xml');
    });
});

describe('Post type sitemap generation', function () {
    test('generates sitemap for posts', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'test-post',
        ]);

        $sitemap = $this->service->generateForPostType('post');

        expect($sitemap->render())->toContain('<?xml');
        expect($sitemap->render())->toContain('<urlset');
        expect($sitemap->render())->toContain('test-post');
    });

    test('generates sitemap for pages', function () {
        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'about-us',
        ]);

        $sitemap = $this->service->generateForPostType('page');

        expect($sitemap->render())->toContain('about-us');
    });

    test('includes only published posts', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'published-post',
        ]);

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::DRAFT,
            'post_slug'   => 'draft-post',
        ]);

        $sitemap = $this->service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('published-post');
        expect($rendered)->not->toContain('draft-post');
    });

    test('excludes posts with future publish date', function () {
        Post::factory()->create([
            'post_type'         => 'post',
            'post_status'       => PostStatus::PUBLISH,
            'post_slug'         => 'future-post',
            'post_published_at' => now()->addDay(),
        ]);

        Post::factory()->create([
            'post_type'         => 'post',
            'post_status'       => PostStatus::PUBLISH,
            'post_slug'         => 'current-post',
            'post_published_at' => now()->subDay(),
        ]);

        $sitemap = $this->service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->not->toContain('future-post');
        expect($rendered)->toContain('current-post');
    });

    test('excludes posts with null publish date', function () {
        Post::factory()->create([
            'post_type'         => 'post',
            'post_status'       => PostStatus::PUBLISH,
            'post_slug'         => 'no-date-post',
            'post_published_at' => null,
        ]);

        $sitemap = $this->service->generateForPostType('post');

        expect($sitemap->render())->not->toContain('no-date-post');
    });

    test('includes change frequency from settings', function () {
        $this->settings->default_change_frequency = 'daily';

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->generateForPostType('post');

        expect($sitemap->render())->toContain('<changefreq>daily</changefreq>');
    });

    test('includes priority from settings', function () {
        $this->settings->default_priority = 0.8;

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->generateForPostType('post');

        expect($sitemap->render())->toContain('<priority>0.8</priority>');
    });

    test('includes last modification date', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->generateForPostType('post');

        expect($sitemap->render())->toContain('<lastmod>');
    });

    test('handles large number of posts without loading all at once', function () {
        Post::factory()->count(15)->create([
            'post_type'         => 'post',
            'post_status'       => PostStatus::PUBLISH,
            'post_published_at' => now()->subDay(),
        ]);

        $sitemap = $this->service->generateForPostType('post');
        $rendered = $sitemap->render();

        // All 15 posts should be in the sitemap
        expect(substr_count($rendered, '<url>'))->toBe(15);
    });
});

describe('Excluded paths', function () {
    test('excludes posts matching exact path', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'public-post',
        ]);

        $privatePost = Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'private-post',
        ]);

        $this->settings->excluded_paths = [$privatePost->url];

        // Need a fresh service to pick up the new settings
        $service = new SitemapService($this->settings);
        $sitemap = $service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('public-post');
        expect($rendered)->not->toContain('private-post');
    });

    test('excludes posts matching wildcard pattern', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'admin-dashboard',
        ]);

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'about',
        ]);

        $this->settings->excluded_paths = ['*/admin-*'];

        $service = new SitemapService($this->settings);
        $sitemap = $service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('about');
        expect($rendered)->not->toContain('admin-dashboard');
    });

    test('supports multiple excluded paths', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'public',
        ]);

        $private = Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'private',
        ]);

        $secret = Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'secret',
        ]);

        $this->settings->excluded_paths = [$private->url, $secret->url];

        $service = new SitemapService($this->settings);
        $sitemap = $service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('public');
        expect($rendered)->not->toContain('>private<');
        expect($rendered)->not->toContain('>secret<');
    });
});

describe('File writing', function () {
    test('writes sitemap index to default filename', function () {
        $this->service->writeToFile();

        expect(File::exists(public_path('sitemap.xml')))->toBeTrue();
    });

    test('writes sitemap index to custom filename', function () {
        $this->service->writeToFile('custom-sitemap.xml');

        expect(File::exists(public_path('custom-sitemap.xml')))->toBeTrue();
    });

    test('written file contains valid XML', function () {
        $this->service->writeToFile();

        $content = File::get(public_path('sitemap.xml'));

        expect($content)->toContain('<?xml');
        expect($content)->toContain('<sitemapindex');
    });
});

describe('Cache management', function () {
    test('clears sitemap cache', function () {
        // Generate sitemaps to populate cache
        $this->service->render();
        $this->service->generateForPostType('post');
        $this->service->generateForPostType('page');

        // Verify cache exists
        expect(Cache::has('sitemap_index'))->toBeTrue();

        // Clear cache
        $this->service->clearCache();

        // Verify cache is cleared
        expect(Cache::has('sitemap_index'))->toBeFalse();
        expect(Cache::has('sitemap_post'))->toBeFalse();
        expect(Cache::has('sitemap_page'))->toBeFalse();
    });
});

describe('Page hierarchy', function () {
    test('builds hierarchical URLs for pages', function () {
        $parent = Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'parent',
            'parent_id'   => null,
        ]);

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'child',
            'parent_id'   => $parent->id,
        ]);

        $sitemap = $this->service->generateForPostType('page');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('/parent');
        expect($rendered)->toContain('/parent/child');
    });

    test('builds deep nested hierarchical URLs (3+ levels)', function () {
        $grandparent = Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'grandparent',
            'parent_id'   => null,
        ]);

        $parent = Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'parent',
            'parent_id'   => $grandparent->id,
        ]);

        Post::factory()->create([
            'post_type'   => 'page',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'child',
            'parent_id'   => $parent->id,
        ]);

        $sitemap = $this->service->generateForPostType('page');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('/grandparent');
        expect($rendered)->toContain('/grandparent/parent');
        expect($rendered)->toContain('/grandparent/parent/child');
    });
});

describe('Image inclusion', function () {
    test('does not include image tags when include_images is disabled', function () {
        $this->settings->include_images = false;

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'post-without-images',
        ]);

        $service = new SitemapService($this->settings);
        $sitemap = $service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->not->toContain('<image:image>');
    });
});

describe('Last modification date', function () {
    test('uses post updated_at for lastmod', function () {
        $specificDate = now()->subDays(5);

        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'dated-post',
            'updated_at'  => $specificDate,
        ]);

        $sitemap = $this->service->generateForPostType('post');
        $rendered = $sitemap->render();

        expect($rendered)->toContain('<lastmod>' . $specificDate->toW3cString() . '</lastmod>');
    });

    test('uses current date when updated_at is null', function () {
        // Create post and manually set updated_at to null
        $post = Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'no-updated-at',
        ]);

        // Force updated_at to null (bypass model timestamps)
        Post::withoutTimestamps(fn () => $post->update(['updated_at' => null]));

        $sitemap = $this->service->generateForPostType('post');
        $rendered = $sitemap->render();

        // Should still have a lastmod tag (using Carbon::now() fallback)
        expect($rendered)->toContain('<lastmod>');
        expect($rendered)->toContain('no-updated-at');
    });
});

describe('Caching behavior', function () {
    test('caches sitemap index results', function () {
        // First call should cache
        $firstResult = $this->service->render();

        // Modify settings - this should NOT affect cached result
        $this->settings->custom_sitemaps = ['/new-sitemap.xml'];

        // Second call should return cached result (without new sitemap)
        $secondResult = $this->service->render();

        expect($firstResult)->toBe($secondResult);
        expect($secondResult)->not->toContain('new-sitemap.xml');
    });

    test('populates cache for post type sitemaps', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'test-post',
        ]);

        // Verify cache doesn't exist initially
        expect(Cache::has('sitemap_post'))->toBeFalse();

        // First call should populate cache
        $this->service->generateForPostType('post');

        // Verify cache was populated
        expect(Cache::has('sitemap_post'))->toBeTrue();

        // Verify we can retrieve from cache
        $cached = Cache::get('sitemap_post');
        expect($cached)->toBeInstanceOf(Sitemap::class);
    });

    test('clearCache allows fresh generation', function () {
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'original-post',
        ]);

        // First call caches result
        $this->service->generateForPostType('post');

        // Add new post
        Post::factory()->create([
            'post_type'   => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug'   => 'new-post',
        ]);

        // Clear cache
        $this->service->clearCache();

        // Now should include the new post
        $freshResult = $this->service->generateForPostType('post')->render();

        expect($freshResult)->toContain('original-post');
        expect($freshResult)->toContain('new-post');
    });
});
