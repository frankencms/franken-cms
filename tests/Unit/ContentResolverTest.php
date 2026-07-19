<?php

use FrankenCms\Models\Post;
use FrankenCms\Services\ContentResolver;
use FrankenCms\Settings\ReadingSettings;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    // Set required settings
    $readingSettings = app(ReadingSettings::class);
    $readingSettings->post_page = 'blog';
    $readingSettings->save();
});

it('resolvePost does not return draft posts', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title'        => 'Draft Post', 'post_slug' => 'draft-post', 'post_status' => 'draft',
        'post_published_at' => now()->subDay(), 'post_type' => 'post',
        'created_at'        => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);

    $resolver->resolvePost('draft-post');
})->throws(NotFoundHttpException::class);

it('resolvePost does not return future-scheduled posts', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title'        => 'Future Post', 'post_slug' => 'future-post', 'post_status' => 'published',
        'post_published_at' => now()->addWeek(), 'post_type' => 'post',
        'created_at'        => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);

    $resolver->resolvePost('future-post');
})->throws(NotFoundHttpException::class);

it('resolvePost returns published posts with past date', function () {
    Post::withoutGlobalScopes()->insert([
        'post_title'        => 'Live Post', 'post_slug' => 'live-post', 'post_status' => 'published',
        'post_published_at' => now()->subDay(), 'post_type' => 'post',
        'created_at'        => now(), 'updated_at' => now(),
    ]);

    $resolver = app(ContentResolver::class);
    $post = $resolver->resolvePost('live-post');

    expect($post->post_slug)->toBe('live-post');
});

it('isPostPath matches exact segment boundaries', function () {
    $resolver = app(ContentResolver::class);

    expect($resolver->isPostPath('blog'))->toBeTrue()
        ->and($resolver->isPostPath('blog/my-post'))->toBeTrue()
        ->and($resolver->isPostPath('blogging-tips'))->toBeFalse()
        ->and($resolver->isPostPath('blogger'))->toBeFalse();
});

it('extractSlugFromPostPath extracts slug correctly', function () {
    $resolver = app(ContentResolver::class);

    expect($resolver->extractSlugFromPostPath('blog/my-post'))->toBe('my-post')
        ->and($resolver->extractSlugFromPostPath('blog/2024/01/my-post'))->toBe('2024/01/my-post');
});
