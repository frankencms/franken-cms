<?php

use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\OgImage\OgImageFeature;

describe('isInstalled', function () {
    test('returns true when spatie/laravel-og-image is installed', function () {
        // dev dependency, present in the test env
        expect(OgImageFeature::isInstalled())->toBeTrue();
    });
});

describe('isEnabled', function () {
    test('respects the config toggle', function () {
        config()->set('franken-cms.og_image.enabled', false);
        expect(OgImageFeature::isEnabled())->toBeFalse();

        config()->set('franken-cms.og_image.enabled', true);
        expect(OgImageFeature::isEnabled())->toBeTrue();
    });
});

describe('templateFor', function () {
    test('returns null when no template is mapped for the post type', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBeNull();
    });

    test('returns null when the mapped view does not exist', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'theme.og-templates.missing']);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBeNull();
    });

    test('returns the mapped view when it exists', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();

        expect(OgImageFeature::templateFor($post))->toBe('franken-cms::help');
    });

    test('returns null for a null post', function () {
        expect(OgImageFeature::templateFor(null))->toBeNull();
    });
});

describe('resolvesFor', function () {
    test('is false when nothing resolves', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();

        expect(OgImageFeature::resolvesFor($post))->toBeFalse();
    });

    test('is true when a template is mapped', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();

        expect(OgImageFeature::resolvesFor($post))->toBeTrue();
    });

    test('is false when the post prefers a twitter summary card', function () {
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();
        $post->setMeta('seo_use_twitter_summary', true);

        expect(OgImageFeature::resolvesFor($post))->toBeFalse();
    });

    test('is true when the post has seo-og media and no template mapped', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();
        $post->media()->create([
            'collection_name'       => 'seo-og',
            'name'                  => 'seo-og-image',
            'file_name'             => 'seo-og.jpg',
            'mime_type'             => 'image/jpeg',
            'disk'                  => 'public',
            'size'                  => 1024,
            'manipulations'         => [],
            'custom_properties'     => [],
            'generated_conversions' => [],
            'responsive_images'     => [],
            'order_column'          => 1,
        ]);

        expect(OgImageFeature::resolvesFor($post))->toBeTrue();
    });

    test('is true with no template and no post media when the site default og-default exists', function () {
        config()->set('franken-cms.og_image.templates', []);
        $post = Post::factory()->create();

        $siteSettings = SiteSettingsMedia::getInstance();
        $siteSettings->media()->create([
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

        expect(OgImageFeature::resolvesFor($post))->toBeTrue();
    });

    test('is false when og_image is disabled even with a template mapped', function () {
        config()->set('franken-cms.og_image.enabled', false);
        config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);
        $post = Post::factory()->create();

        expect(OgImageFeature::resolvesFor($post))->toBeFalse();
    });

    test('is false for a null post even when a site default og-default exists', function () {
        config()->set('franken-cms.og_image.templates', []);

        $siteSettings = SiteSettingsMedia::getInstance();
        $siteSettings->media()->create([
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

        expect(OgImageFeature::resolvesFor(null))->toBeFalse();
    });
});
