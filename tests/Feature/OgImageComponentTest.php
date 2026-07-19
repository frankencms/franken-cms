<?php

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Services\CurrentPageService;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    config()->set('franken-cms.og_image.enabled', true);
});

function makeSeoOgMedia(Post $post): void
{
    $post->media()->create([
        'collection_name'       => 'seo-og',
        'name'                  => 'seo-og-image',
        'file_name'             => 'seo-og.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'conversions_disk'      => 'public',
        'size'                  => 1024,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
        'order_column'          => 1,
    ]);
}

test('renders nothing when the feature is disabled', function () {
    config()->set('franken-cms.og_image.enabled', false);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});

test('renders the mapped template through the spatie component', function () {
    config()->set('franken-cms.og_image.templates', ['post' => 'test-fixtures::og-post']);
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain('data-og-image')
        ->and($html)->toContain('data-og-hash');
});

test('passes a manual upload url through without generation', function () {
    config()->set('franken-cms.og_image.templates', []);
    $post = Post::factory()->create();
    makeSeoOgMedia($post);
    app(CurrentPageService::class)->setPage($post);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain($post->getFirstMedia('seo-og')->getFullUrl('og'))
        ->and($html)->toContain('data-og-url');
});

test('falls back to the site default og image url when no template or post media', function () {
    config()->set('franken-cms.og_image.templates', []);
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    $siteSettings = SiteSettingsMedia::getInstance();
    $siteSettings->media()->create([
        'collection_name'       => 'og-default',
        'name'                  => 'default-og',
        'file_name'             => 'default-og.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'conversions_disk'      => 'public',
        'size'                  => 1024,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
        'order_column'          => 1,
    ]);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain($siteSettings->getFirstMedia('og-default')->getFullUrl('og'))
        ->and($html)->toContain('data-og-url');
});

test('renders nothing when there is a site default but no current page (non-CMS route)', function () {
    config()->set('franken-cms.og_image.templates', []);

    $siteSettings = SiteSettingsMedia::getInstance();
    $siteSettings->media()->create([
        'collection_name'       => 'og-default',
        'name'                  => 'default-og',
        'file_name'             => 'default-og.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'conversions_disk'      => 'public',
        'size'                  => 1024,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
        'order_column'          => 1,
    ]);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});

test('renders nothing when nothing resolves', function () {
    config()->set('franken-cms.og_image.templates', []);
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});

test('renders the default fallback template when nothing else resolves', function () {
    config()->set('franken-cms.og_image.templates', []);
    config()->set('franken-cms.og_image.default_template', 'test-fixtures::og-post');
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain('data-og-image')
        ->and($html)->toContain('data-og-hash');
});

test('prefers the site default image over the fallback template', function () {
    config()->set('franken-cms.og_image.templates', []);
    config()->set('franken-cms.og_image.default_template', 'test-fixtures::og-post');
    $post = Post::factory()->create();
    app(CurrentPageService::class)->setPage($post);

    $siteSettings = SiteSettingsMedia::getInstance();
    $siteSettings->media()->create([
        'collection_name'       => 'og-default',
        'name'                  => 'default-og',
        'file_name'             => 'default-og.jpg',
        'mime_type'             => 'image/jpeg',
        'disk'                  => 'public',
        'conversions_disk'      => 'public',
        'size'                  => 1024,
        'manipulations'         => [],
        'custom_properties'     => [],
        'generated_conversions' => [],
        'responsive_images'     => [],
        'order_column'          => 1,
    ]);

    $html = Blade::render('<x-franken-og-image />');

    expect($html)->toContain($siteSettings->getFirstMedia('og-default')->getFullUrl('og'))
        ->and($html)->not->toContain('data-og-hash');
});

test('renders nothing when there is no current page and no site default', function () {
    config()->set('franken-cms.og_image.templates', []);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});

test('renders nothing for a summary-card post even with a mapped template', function () {
    config()->set('franken-cms.og_image.templates', ['post' => 'test-fixtures::og-post']);
    $post = Post::factory()->create();
    $post->setMeta('seo_use_twitter_summary', true);
    app(CurrentPageService::class)->setPage($post);

    expect(trim(Blade::render('<x-franken-og-image />')))->toBe('');
});
