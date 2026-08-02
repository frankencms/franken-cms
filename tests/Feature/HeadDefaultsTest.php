<?php

use FrankenCms\Http\Middleware\AddSeoDefaults;
use FrankenCms\Models\Post;
use FrankenCms\Services\CurrentPageService;
use FrankenCms\Settings\SeoSettings;
use FrankenCms\Tests\Support\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Laravel\Head\Facades\Head;
use Laravel\Head\HeadBuilder;

beforeEach(function () {
    AddSeoDefaults::flushRegisteredCallbacks();
});

afterEach(function () {
    AddSeoDefaults::flushRegisteredCallbacks();
});

function seoDefaultSettings(array $overrides = []): void
{
    $settings = app(SeoSettings::class);
    $settings->site_name = $overrides['site_name'] ?? 'Franken Site';
    $settings->append_site_name = $overrides['append_site_name'] ?? true;
    $settings->site_name_position = $overrides['site_name_position'] ?? 'append';
    $settings->title_separator = $overrides['title_separator'] ?? '-';
    $settings->default_meta_description = $overrides['default_meta_description'] ?? 'Default description';
    $settings->save();
}

function runHeadMiddleware(): string
{
    app(AddSeoDefaults::class)->handle(Request::create('/'), fn ($request) => response('ok'));

    return Head::render()->toHtml();
}

function makeHeadPost(array $attributes = []): Post
{
    $author = User::create([
        'name'     => 'Head Author',
        'email'    => uniqid('head-') . '@example.test',
        'password' => 'secret',
    ]);

    return Post::factory()->create([...$attributes, 'post_author_id' => $author->id]);
}

it('renders the site name as the title on pages without a CMS post', function () {
    seoDefaultSettings();

    $html = runHeadMiddleware();

    expect($html)->toContain('<title>Franken Site</title>')
        ->and($html)->toContain('property="og:site_name"')
        ->and($html)->toContain('name="description" content="Default description"');
});

it('appends the site name suffix to post titles', function () {
    seoDefaultSettings();

    $post = makeHeadPost(['post_title' => 'Hello World']);
    app(CurrentPageService::class)->setPage($post);

    $html = runHeadMiddleware();

    expect($html)->toContain('<title>Hello World - Franken Site</title>');
});

it('prepends the site name when configured', function () {
    seoDefaultSettings(['site_name_position' => 'prepend']);

    $post = makeHeadPost(['post_title' => 'Hello World']);
    app(CurrentPageService::class)->setPage($post);

    $html = runHeadMiddleware();

    expect($html)->toContain('<title>Franken Site - Hello World</title>');
});

it('lets app-registered defaults override the CMS defaults', function () {
    seoDefaultSettings();

    AddSeoDefaults::registering(fn (HeadBuilder $head) => $head->description('App override'));

    $html = runHeadMiddleware();

    expect($html)->toContain('name="description" content="App override"')
        ->and($html)->not->toContain('Default description');
});

it('emits icon tags only for generated favicon files that exist', function () {
    seoDefaultSettings();

    $storagePath = storage_path('app/public/favicons');
    File::ensureDirectoryExists($storagePath);
    File::put("{$storagePath}/favicon-32x32.png", 'png');
    File::put("{$storagePath}/apple-touch-icon-152x152.png", 'png');

    $html = runHeadMiddleware();

    expect($html)->toContain('rel="icon" href="/favicon-32x32.png"')
        ->and($html)->toContain('rel="apple-touch-icon" href="/apple-touch-icon-152x152.png"')
        ->and($html)->not->toContain('favicon-16x16.png')
        ->and($html)->not->toContain('msapplication');

    File::deleteDirectory($storagePath);
});

it('renders the per-post og title/description overrides without the site-name affix', function () {
    seoDefaultSettings();

    $post = makeHeadPost(['post_title' => 'Hello World']);
    $post->setMeta('seo_og_title', 'Custom OG Title');
    $post->setMeta('seo_og_description', 'Custom OG description');
    app(CurrentPageService::class)->setPage($post);

    $html = runHeadMiddleware();

    expect($html)->toContain('property="og:title" content="Custom OG Title"')
        ->and($html)->toContain('property="og:description" content="Custom OG description"')
        ->and($html)->not->toContain('Custom OG Title - Franken Site');
});

it('adds breadcrumb json-ld schema for posts', function () {
    seoDefaultSettings();

    $post = makeHeadPost(['post_title' => 'Crumby Post', 'post_type' => 'post']);
    app(CurrentPageService::class)->setPage($post);

    $html = runHeadMiddleware();

    expect($html)->toContain('application/ld+json')
        ->and($html)->toContain('BreadcrumbList');
});
