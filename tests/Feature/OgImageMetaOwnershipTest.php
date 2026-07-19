<?php

use FrankenCms\Http\Middleware\AddSeoDefaults;
use FrankenCms\Models\Post;
use FrankenCms\Models\SiteSettingsMedia;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Tests\Support\User;
use Illuminate\Http\Request;

function makeOgOwnershipPost(array $attributes = []): Post
{
    $author = User::create([
        'name'     => 'Og Owner',
        'email'    => uniqid('og-owner-') . '@example.test',
        'password' => 'secret',
    ]);

    return Post::factory()->create([...$attributes, 'post_author_id' => $author->id]);
}

function makeOgOwnershipSeoMedia(Post $post): void
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

function runOgOwnershipMiddleware(): string
{
    $middleware = app(AddSeoDefaults::class);

    $middleware->handle(Request::create('/'), fn ($request) => response('ok'));

    return seo()->render()->toHtml();
}

test('classic path still emits og:image when the feature is disabled', function () {
    config()->set('franken-cms.og_image.enabled', false);

    $post = makeOgOwnershipPost();
    makeOgOwnershipSeoMedia($post);
    app(CurrentPageService::class)->setPage($post);

    $html = runOgOwnershipMiddleware();

    expect($html)->toContain('property="og:image"')
        ->and($html)->toContain('property="og:title"')
        ->and($html)->toContain('property="og:url"')
        ->and($html)->toContain('name="twitter:card"');
});

test('suppresses og:image, twitter:image and twitter:card when the feature resolves', function () {
    config()->set('franken-cms.og_image.enabled', true);
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);

    $post = makeOgOwnershipPost();
    app(CurrentPageService::class)->setPage($post);

    $html = runOgOwnershipMiddleware();

    expect($html)->toContain('property="og:title"')
        ->and($html)->toContain('property="og:url"')
        ->and($html)->not->toContain('property="og:image"')
        ->and($html)->not->toContain('name="twitter:card"')
        ->and($html)->not->toContain('name="twitter:image"');
});

test('summary-card posts keep the classic tags even when a template is mapped', function () {
    config()->set('franken-cms.og_image.enabled', true);
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);

    $post = makeOgOwnershipPost();
    $post->setMeta('seo_use_twitter_summary', true);
    app(CurrentPageService::class)->setPage($post);

    $html = runOgOwnershipMiddleware();

    expect($html)->toContain('name="twitter:card" content="summary"');
});

test('hand-coded routes with no current page keep classic og:image and twitter:card even with a site default', function () {
    config()->set('franken-cms.og_image.enabled', true);
    config()->set('franken-cms.og_image.templates', ['post' => 'franken-cms::help']);

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

    // No current page set - simulates a hand-coded, non-CMS route.
    $html = runOgOwnershipMiddleware();

    expect($html)->toContain('property="og:image"')
        ->and($html)->toContain('name="twitter:card"');
});
