<?php

use FrankenCms\Enums\PostStatus;
use FrankenCms\Models\Post;
use FrankenCms\Services\SitemapService;
use FrankenCms\Settings\SitemapSettings;
use FrankenCms\Tests\Support\User;
use Illuminate\Support\Facades\File;

beforeEach(function () {
    // Create a test user with ID 1 for post author relationship
    User::create([
        'id' => 1,
        'name' => 'Test User',
        'email' => 'test@example.com',
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

    // Mock settings with defaults
    $this->settings = Mockery::mock(SitemapSettings::class);
    $this->settings->enabled = true;
    $this->settings->included_post_types = ['post', 'page'];
    $this->settings->default_change_frequency = 'weekly';
    $this->settings->default_priority = 0.5;
    $this->settings->max_urls_per_sitemap = 50000;
    $this->settings->excluded_paths = [];
    $this->settings->include_images = true;

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

describe('Post filtering', function () {
    test('includes only published posts', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::DRAFT,
        ]);

        $sitemap = $this->service->render();

        // Should contain 1 URL (only published post)
        expect(substr_count($sitemap, '<url>'))->toBe(1);
    });

    test('includes only configured post types', function () {
        $this->settings->included_post_types = ['post'];

        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        Post::factory()->create([
            'post_type' => 'page',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        // Should contain 1 URL (only post type)
        expect(substr_count($sitemap, '<url>'))->toBe(1);
    });

    test('excludes posts with future publish date', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_published_at' => now()->addDay(),
        ]);

        $sitemap = $this->service->render();

        // Should contain 0 URLs (future post)
        expect(substr_count($sitemap, '<url>'))->toBe(0);
    });

    test('includes posts with past publish date', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_published_at' => now()->subDay(),
        ]);

        $sitemap = $this->service->render();

        // Should contain 1 URL
        expect(substr_count($sitemap, '<url>'))->toBe(1);
    });

    test('includes posts with null publish date', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_published_at' => null,
        ]);

        $sitemap = $this->service->render();

        // Should contain 1 URL
        expect(substr_count($sitemap, '<url>'))->toBe(1);
    });
});

describe('Excluded paths', function () {
    test('excludes posts matching exact path', function () {
        $privatePost = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'private-post',
        ]);

        $publicPost = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'public-post',
        ]);

        $this->settings->excluded_paths = [$privatePost->url];

        $sitemap = $this->service->render();

        // Should contain 1 URL (only public post)
        expect(substr_count($sitemap, '<url>'))->toBe(1);
        expect($sitemap)->toContain('public-post');
        expect($sitemap)->not->toContain('private-post');
    });

    test('excludes posts matching wildcard pattern', function () {
        $adminPost = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'admin-stuff',
        ]);

        $publicPost = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'about',
        ]);

        // Use wildcard pattern matching URL structure
        $this->settings->excluded_paths = ['*/admin-*'];

        $sitemap = $this->service->render();

        // Should contain 1 URL (only about post, admin-stuff excluded by wildcard)
        expect(substr_count($sitemap, '<url>'))->toBe(1);
        expect($sitemap)->toContain('/about');
        expect($sitemap)->not->toContain('admin-stuff');
    });

    test('supports multiple excluded paths', function () {
        $private = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'private',
        ]);

        $secret = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'secret',
        ]);

        $public = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'public',
        ]);

        $this->settings->excluded_paths = [$private->url, $secret->url];

        $sitemap = $this->service->render();

        // Should contain 1 URL (only public)
        expect(substr_count($sitemap, '<url>'))->toBe(1);
        expect($sitemap)->toContain('/public');
    });
});

describe('Sitemap generation', function () {
    test('generates valid XML sitemap', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        expect($sitemap)->toContain('<?xml');
        expect($sitemap)->toContain('<urlset');
        expect($sitemap)->toContain('<url>');
        expect($sitemap)->toContain('<loc>');
        expect($sitemap)->toContain('<lastmod>');
        expect($sitemap)->toContain('<changefreq>');
        expect($sitemap)->toContain('<priority>');
    });

    test('includes change frequency from settings', function () {
        $this->settings->default_change_frequency = 'daily';

        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        expect($sitemap)->toContain('<changefreq>daily</changefreq>');
    });

    test('includes priority from settings', function () {
        $this->settings->default_priority = 0.8;

        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        expect($sitemap)->toContain('<priority>0.8</priority>');
    });

    test('includes post URL in sitemap', function () {
        $post = Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
            'post_slug' => 'test-post',
        ]);

        $sitemap = $this->service->render();

        expect($sitemap)->toContain($post->url);
    });

    test('includes last modification date', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        expect($sitemap)->toContain('<lastmod>');
    });
});

describe('Sitemap index generation', function () {
    test('creates single sitemap when under max URLs', function () {
        $this->settings->max_urls_per_sitemap = 10;

        // Create 5 posts (under max)
        Post::factory()->count(5)->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        // Should be a regular sitemap, not an index
        expect($sitemap)->toContain('<urlset');
        expect($sitemap)->not->toContain('<sitemapindex');
    });

    test('creates sitemap index when exceeding max URLs', function () {
        $this->settings->max_urls_per_sitemap = 2;

        // Create 5 posts (over max)
        Post::factory()->count(5)->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $sitemap = $this->service->render();

        // Should be a sitemap index
        expect($sitemap)->toContain('<sitemapindex');
        expect($sitemap)->not->toContain('<urlset');
        expect($sitemap)->toContain('<sitemap>');
    });
});

describe('File writing', function () {
    test('writes sitemap to default filename', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $this->service->writeToFile();

        expect(File::exists(public_path('sitemap.xml')))->toBeTrue();
    });

    test('writes sitemap to custom filename', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $this->service->writeToFile('custom-sitemap.xml');

        expect(File::exists(public_path('custom-sitemap.xml')))->toBeTrue();
    });

    test('written file contains valid XML', function () {
        Post::factory()->create([
            'post_type' => 'post',
            'post_status' => PostStatus::PUBLISH,
        ]);

        $this->service->writeToFile();

        $content = File::get(public_path('sitemap.xml'));

        expect($content)->toContain('<?xml');
        expect($content)->toContain('<urlset');
    });
});
