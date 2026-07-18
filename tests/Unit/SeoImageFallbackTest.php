<?php

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Settings\SeoSettings;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

describe('Post → seoOgImage fallback chain', function () {

    it('returns seo-og image when present', function () {
        $seoOgMedia = Mockery::mock(Media::class);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(true);
        $post->shouldReceive('getFirstMedia')->with('seo-og')->andReturn($seoOgMedia);

        expect($post->seoOgImage())->toBe($seoOgMedia);
    });

    it('falls back to featured image when no seo-og image', function () {
        $featuredMedia = Mockery::mock(Media::class);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(false);
        $post->shouldReceive('hasMedia')->with('featured')->andReturn(true);
        $post->shouldReceive('getFirstMedia')->with('featured')->andReturn($featuredMedia);

        expect($post->seoOgImage())->toBe($featuredMedia);
    });

    it('falls back to site default when no seo-og and no featured image', function () {
        // Create the SiteSettingsMedia record, then attach a media record to it
        $siteSettings = SiteSettingsMedia::getInstance();
        $defaultOgMedia = $siteSettings->media()->create([
            'collection_name'       => 'og-default',
            'name'                  => 'default-og',
            'file_name'             => 'default-og.jpg',
            'mime_type'             => 'image/jpeg',
            'disk'                  => 'public',
            'size'                  => 1024,
            'manipulations'         => [],
            'custom_properties'     => [],
            'generated_conversions' => [],
            'responsive_images'     => [],
            'order_column'          => 1,
        ]);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(false);
        $post->shouldReceive('hasMedia')->with('featured')->andReturn(false);

        $result = $post->seoOgImage();

        expect($result)->toBeInstanceOf(Media::class);
        expect($result->id)->toBe($defaultOgMedia->id);
    });

    it('returns null when no images are available', function () {
        // Ensure a clean SiteSettingsMedia with no media
        SiteSettingsMedia::getInstance();

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(false);
        $post->shouldReceive('hasMedia')->with('featured')->andReturn(false);

        expect($post->seoOgImage())->toBeNull();
    });

    it('prioritizes seo-og over featured image', function () {
        $seoOgMedia = Mockery::mock(Media::class);

        $post = Mockery::mock(Post::class)->makePartial();
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(true);
        $post->shouldReceive('getFirstMedia')->with('seo-og')->andReturn($seoOgMedia);
        // featured should never be checked
        $post->shouldNotReceive('hasMedia')->with('featured');

        expect($post->seoOgImage())->toBe($seoOgMedia);
    });
});

describe('Post → seoTwitterImage fallback chain', function () {

    it('falls through to seoOgImage which includes featured fallback', function () {
        $featuredMedia = Mockery::mock(Media::class);

        $post = Mockery::mock(Post::class)->makePartial();

        // Twitter summary disabled globally
        $seoSettings = new SeoSettings;
        $seoSettings->use_twitter_summary_card = false;
        app()->instance(SeoSettings::class, $seoSettings);

        // getMeta returns null (no per-post override), falls back to global (false)
        $post->shouldReceive('getMeta')->with('seo_use_twitter_summary', false)->andReturn(false);

        // seoOgImage chain: no seo-og, has featured
        $post->shouldReceive('hasMedia')->with('seo-og')->andReturn(false);
        $post->shouldReceive('hasMedia')->with('featured')->andReturn(true);
        $post->shouldReceive('getFirstMedia')->with('featured')->andReturn($featuredMedia);

        expect($post->seoTwitterImage())->toBe($featuredMedia);
    });
});
