<?php

use FrankenCms\Models\Post;
use FrankenCms\Services\SeoService;
use FrankenCms\Settings\SeoSettings;
use FrankenCms\Tests\Support\User;

function makeSeoTitlePost(array $attributes = []): Post
{
    $author = User::create([
        'name'     => 'Title Author',
        'email'    => uniqid('seo-title-') . '@example.test',
        'password' => 'secret',
    ]);

    return Post::factory()->create([...$attributes, 'post_author_id' => $author->id]);
}

it('returns the raw post title without site name composition', function () {
    $settings = app(SeoSettings::class);
    $settings->site_name = 'My Site';
    $settings->append_site_name = true;
    $settings->title_separator = '-';
    $settings->save();

    $post = makeSeoTitlePost(['post_title' => 'Hello World']);

    expect(app(SeoService::class)->getTitle($post))->toBe('Hello World');
});

it('prefers the custom seo_title meta over the post title', function () {
    $post = makeSeoTitlePost(['post_title' => 'Hello World']);
    $post->setMeta('seo_title', 'Custom SEO Title');

    expect(app(SeoService::class)->getTitle($post))->toBe('Custom SEO Title');
});

it('falls back to the site name when there is no post', function () {
    $settings = app(SeoSettings::class);
    $settings->site_name = 'My Site';
    $settings->save();

    expect(app(SeoService::class)->getTitle(null))->toBe('My Site');
});
